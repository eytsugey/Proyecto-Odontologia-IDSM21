<?php

require_once __DIR__ . '/google_calendar_service.php';
require_once __DIR__ . '/helpers/schema.php';

function obtenerCitaParaGoogle(PDO $pdo, int $citaId): ?array
{
    $mode = citasDoctorMode($pdo);

    $doctorSelect = "'Doctor no asignado' AS doctor_nombre";
    $doctorJoin = '';

    if ($mode === 'cedula') {
        $doctorSelect = "COALESCE(d.nombre, c.cedula_doctor, 'Doctor no asignado') AS doctor_nombre";
        $doctorJoin = "LEFT JOIN doctores d ON d.cedula = c.cedula_doctor";
    } elseif ($mode === 'doctor_id') {
        if (tableExists($pdo, 'doctores') && columnExists($pdo, 'doctores', 'id')) {
            $doctorSelect = "COALESCE(d.nombre, CONCAT('Doctor ', c.doctor_id), 'Doctor no asignado') AS doctor_nombre";
            $doctorJoin = "LEFT JOIN doctores d ON d.id = c.doctor_id";
        } else {
            $doctorSelect = "COALESCE(CONCAT('Doctor ', c.doctor_id), 'Doctor no asignado') AS doctor_nombre";
        }
    }

    $sql = "
        SELECT
            c.id,
            c.fecha,
            c.hora,
            c.motivo_consulta,
            c.estado,
            c.google_event_id,
            p.nombre AS paciente_nombre,
            p.telefono AS paciente_telefono,
            {$doctorSelect}
        FROM citas c
        JOIN pacientes p ON p.id = c.paciente_id
        {$doctorJoin}
        WHERE c.id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$citaId]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        return null;
    }

    $cita['estado'] = citasEstadoNormalizado((string)($cita['estado'] ?? 'pendiente'));
    $cita['fecha'] = (string)($cita['fecha'] ?? '');
    $cita['hora'] = substr((string)($cita['hora'] ?? ''), 0, 8);
    $cita['motivo_consulta'] = trim((string)($cita['motivo_consulta'] ?? ''));
    $cita['paciente_nombre'] = trim((string)($cita['paciente_nombre'] ?? ''));
    $cita['paciente_telefono'] = trim((string)($cita['paciente_telefono'] ?? ''));
    $cita['doctor_nombre'] = trim((string)($cita['doctor_nombre'] ?? 'Doctor no asignado'));
    $cita['google_event_id'] = trim((string)($cita['google_event_id'] ?? ''));

    return $cita;
}

function guardarGoogleEventIdEnCita(PDO $pdo, int $citaId, ?string $googleEventId): void
{
    $stmt = $pdo->prepare('UPDATE citas SET google_event_id = ? WHERE id = ?');
    $stmt->execute([$googleEventId, $citaId]);
}

function esErrorGoogleNotFound(Throwable $e): bool
{
    $mensaje = $e->getMessage();

    return stripos($mensaje, 'Not Found') !== false
        || stripos($mensaje, '"code": 404') !== false
        || stripos($mensaje, '404') !== false;
}

function sincronizarCitaConGoogle(PDO $pdo, int $citaId): array
{
    $cita = obtenerCitaParaGoogle($pdo, $citaId);

    if (!$cita) {
        throw new Exception('La cita no existe para sincronizar con Google.');
    }

    $estado = $cita['estado'];
    $googleEventId = $cita['google_event_id'];

    if ($estado === 'confirmada') {
        if ($googleEventId === '') {
            $evento = googleCalendarCreateEvent($cita);
            $nuevoGoogleEventId = $evento->getId();

            guardarGoogleEventIdEnCita($pdo, $citaId, $nuevoGoogleEventId);

            return [
                'accion' => 'creado',
                'google_event_id' => $nuevoGoogleEventId,
            ];
        }

        try {
            $evento = googleCalendarUpdateEvent($googleEventId, $cita);

            return [
                'accion' => 'actualizado',
                'google_event_id' => $evento->getId(),
            ];
        } catch (Throwable $e) {
            if (!esErrorGoogleNotFound($e)) {
                throw $e;
            }

            $evento = googleCalendarCreateEvent($cita);
            $nuevoGoogleEventId = $evento->getId();

            guardarGoogleEventIdEnCita($pdo, $citaId, $nuevoGoogleEventId);

            return [
                'accion' => 'creado',
                'google_event_id' => $nuevoGoogleEventId,
            ];
        }
    }

    if (in_array($estado, ['cancelada', 'pendiente', 'atendida'], true)) {
        if ($googleEventId !== '') {
            try {
                googleCalendarDeleteEvent($googleEventId);
            } catch (Throwable $e) {
                if (!esErrorGoogleNotFound($e)) {
                    throw $e;
                }
            }

            guardarGoogleEventIdEnCita($pdo, $citaId, null);

            return [
                'accion' => 'eliminado',
                'google_event_id' => null,
            ];
        }

        return [
            'accion' => 'sin_cambios',
            'google_event_id' => null,
        ];
    }

    return [
        'accion' => 'sin_cambios',
        'google_event_id' => $googleEventId !== '' ? $googleEventId : null,
    ];
}