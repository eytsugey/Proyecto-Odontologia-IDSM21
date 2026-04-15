<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/schema.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/google_citas_sync.php';

requireLogin(['secretaria', 'doctor']);

header('Content-Type: application/json; charset=utf-8');

function jsonResponse(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function mapaColorEstado(string $estado): string {
    return match ($estado) {
        'confirmada' => '#27ae60',
        'cancelada' => '#e74c3c',
        'atendida' => '#2980b9',
        default => '#f39c12',
    };
}

function obtenerJoinDoctor(PDO $pdo): array {
    $mode = citasDoctorMode($pdo);

    if ($mode === 'cedula') {
        return [
            'join' => 'LEFT JOIN doctores d ON d.cedula = c.cedula_doctor',
            'select' => 'COALESCE(d.nombre, c.cedula_doctor) AS doctor'
        ];
    }

    if ($mode === 'doctor_id') {
        return [
            'join' => '',
            'select' => "CONCAT('Doctor ', c.doctor_id) AS doctor"
        ];
    }

    return [
        'join' => '',
        'select' => "'' AS doctor"
    ];
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'eventos') {
    $start = $_GET['start'] ?? null;
    $end = $_GET['end'] ?? null;

    if (!$start || !$end) {
        jsonResponse(['ok' => false, 'message' => 'Faltan fechas de rango.'], 422);
    }

    $joinDoctor = obtenerJoinDoctor($pdo);

    $sql = "
        SELECT
            c.id,
            c.fecha,
            c.hora,
            p.nombre AS paciente,
            c.motivo_consulta,
            LOWER(TRIM(c.estado)) AS estado,
            {$joinDoctor['select']}
        FROM citas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        {$joinDoctor['join']}
        WHERE c.fecha >= ? AND c.fecha < ?
        ORDER BY c.fecha ASC, c.hora ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start, $end]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $eventos = array_map(function ($row) {
        $estado = citasEstadoNormalizado($row['estado'] ?? 'pendiente');
        $hora = substr((string)$row['hora'], 0, 5);
        $motivo = trim((string)($row['motivo_consulta'] ?? ''));
        $doctor = trim((string)($row['doctor'] ?? ''));

        $titleParts = [$hora . ' - ' . $row['paciente']];
        if ($motivo !== '') {
            $titleParts[] = $motivo;
        }

        return [
            'id' => (int)$row['id'],
            'title' => implode(' | ', $titleParts),
            'start' => $row['fecha'] . 'T' . substr((string)$row['hora'], 0, 8),
            'allDay' => false,
            'backgroundColor' => mapaColorEstado($estado),
            'borderColor' => mapaColorEstado($estado),
            'extendedProps' => [
                'paciente' => $row['paciente'],
                'motivo' => $motivo,
                'doctor' => $doctor,
                'estado' => $estado,
                'hora' => $hora,
            ]
        ];
    }, $rows);

    jsonResponse($eventos);
}

if ($action === 'agenda') {
    $fecha = $_GET['fecha'] ?? null;

    if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        jsonResponse(['ok' => false, 'message' => 'Fecha inválida.'], 422);
    }

    $joinDoctor = obtenerJoinDoctor($pdo);

    $sql = "
        SELECT
            c.id,
            c.paciente_id,
            c.fecha,
            c.hora,
            p.nombre,
            c.motivo_consulta,
            LOWER(TRIM(c.estado)) AS estado,
            {$joinDoctor['select']}
        FROM citas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        {$joinDoctor['join']}
        WHERE c.fecha = ?
        ORDER BY c.hora ASC, c.id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fecha]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($citas as &$cita) {
        $cita['estado'] = citasEstadoNormalizado($cita['estado'] ?? 'pendiente');
        $cita['hora'] = substr((string)$cita['hora'], 0, 8);
    }
    unset($cita);

    jsonResponse($citas);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'estado') {
    $id = (int)($_POST['cita_id'] ?? 0);
    $estado = citasEstadoNormalizado($_POST['estado'] ?? '');

    if ($id <= 0) {
        jsonResponse(['ok' => false, 'message' => 'Cita inválida.'], 422);
    }

    $permitidos = ['pendiente', 'confirmada', 'cancelada', 'atendida'];
    if (!in_array($estado, $permitidos, true)) {
        jsonResponse(['ok' => false, 'message' => 'Estado inválido.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE citas SET estado = ? WHERE id = ?');
    $stmt->execute([$estado, $id]);

    $respuesta = [
        'ok' => true,
        'message' => 'Estado actualizado correctamente.',
        'cita_id' => $id,
        'estado' => $estado,
        'google_ok' => false,
        'google_message' => null,
        'google_error' => null,
    ];

    try {
        $resultadoGoogle = sincronizarCitaConGoogle($pdo, $id);

        $accionGoogle = $resultadoGoogle['accion'] ?? 'sin_cambios';

        $respuesta['google_ok'] = true;

        if ($accionGoogle === 'creado') {
            $respuesta['google_message'] = 'Evento creado en Google Calendar.';
        } elseif ($accionGoogle === 'actualizado') {
            $respuesta['google_message'] = 'Evento actualizado en Google Calendar.';
        } elseif ($accionGoogle === 'eliminado') {
            $respuesta['google_message'] = 'Evento eliminado de Google Calendar.';
        } else {
            $respuesta['google_message'] = 'Sin cambios necesarios en Google Calendar.';
        }

        $respuesta['google_event_id'] = $resultadoGoogle['google_event_id'] ?? null;
    } catch (Throwable $e) {
        $respuesta['google_ok'] = false;
        $respuesta['google_error'] = 'La cita se guardó, pero falló la sincronización con Google: ' . $e->getMessage();
    }

    jsonResponse($respuesta);
}

jsonResponse(['ok' => false, 'message' => 'Acción no válida.'], 404);