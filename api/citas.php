<?php
session_start();
require_once __DIR__ . '/config/database.php';

function redirectPublic(string $path): never {
    header('Location: /Proyecto-Odontologia-IDSM21/public/' . $path);
    exit;
}

function calcularEdadDesdeFecha(?string $fechaNacimiento): ?int {
    if (!$fechaNacimiento) {
        return null;
    }

    try {
        $nacimiento = new DateTime($fechaNacimiento);
        $hoy = new DateTime('today');
    } catch (Exception $e) {
        return null;
    }

    if ($nacimiento > $hoy) {
        return null;
    }

    return $nacimiento->diff($hoy)->y;
}

function validarChoqueCita(PDO $pdo, int $doctorId, ?string $fecha, ?string $hora, int $ignorarId = 0): bool {
    $sql = 'SELECT COUNT(*) FROM citas WHERE doctor_id = ? AND fecha = ? AND hora = ? AND estado <> "cancelada"';
    $params = [$doctorId, $fecha, $hora];

    if ($ignorarId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarId;
    }

    $check = $pdo->prepare($sql);
    $check->execute($params);

    return (int)$check->fetchColumn() > 0;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'solicitar') {
    $nombre = trim($_POST['nombre'] ?? '');
    $sexo = $_POST['sexo'] ?? null;
    $edad = (int)($_POST['edad'] ?? 0);
    $fechaNacimiento = $_POST['fecha_nacimiento'] ?: null;
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaPreferida = $_POST['fecha_preferida'] ?: null;
    $horaPreferida = $_POST['hora_preferida'] ?: null;
    $motivo = trim($_POST['motivo'] ?? '');

    if (
        $nombre === '' ||
        !$sexo ||
        $edad < 0 ||
        !$fechaNacimiento ||
        $telefono === '' ||
        !$fechaPreferida ||
        !$horaPreferida ||
        $motivo === ''
    ) {
        redirectPublic('agendar-cita.php?error=1');
    }

    $hoy = new DateTime('today');
    $fechaPreferidaObj = DateTime::createFromFormat('Y-m-d', $fechaPreferida);
    if (!$fechaPreferidaObj || $fechaPreferidaObj->format('Y-m-d') !== $fechaPreferida || $fechaPreferidaObj < $hoy) {
        redirectPublic('agendar-cita.php?error=fecha_pasada');
    }

    $edadCalculada = calcularEdadDesdeFecha($fechaNacimiento);
    if ($edadCalculada === null) {
        redirectPublic('agendar-cita.php?error=fecha_nacimiento');
    }

    if ($edadCalculada !== $edad) {
        redirectPublic('agendar-cita.php?error=edad_incorrecta');
    }

    $stmt = $pdo->prepare('INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES (?,?,?,?,?)');
    $stmt->execute([
        $nombre,
        $sexo,
        $edad,
        $fechaNacimiento,
        $telefono,
    ]);

    $pacienteId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $pacienteId,
        1,
        $fechaPreferida,
        $horaPreferida,
        $motivo,
        'pendiente',
    ]);

    redirectPublic('solicitud-enviada.php');
}

require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'actualizar') {
    $citaId = (int)($_POST['cita_id'] ?? 0);
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);
    $doctorId = 1;
    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;

    if (!$citaId || !$pacienteId || !$fecha || !$hora) {
        redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&error=datos');
    }

    if (validarChoqueCita($pdo, $doctorId, $fecha, $hora, $citaId)) {
        redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&choque=1');
    }

    $stmt = $pdo->prepare('UPDATE citas SET fecha = ?, hora = ? WHERE id = ?');
    $stmt->execute([$fecha, $hora, $citaId]);

    redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&actualizada=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = 1;
    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;

    if (validarChoqueCita($pdo, $doctorId, $fecha, $hora)) {
        redirectPublic('citas.php?choque=1');
    }

    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        (int)($_POST['paciente_id'] ?? 0),
        $doctorId,
        $fecha,
        $hora,
        trim($_POST['motivo_consulta'] ?? ''),
        $_POST['estado'] ?? 'confirmada',
    ]);

    redirectPublic('citas.php?ok=1');
}

if (isset($_GET['confirmar'])) {
    $pdo->prepare('UPDATE citas SET estado = "confirmada" WHERE id = ?')->execute([(int)$_GET['confirmar']]);
    redirectPublic('citas.php');
}

if (isset($_GET['cancelar'])) {
    $pdo->prepare('UPDATE citas SET estado = "cancelada" WHERE id = ?')->execute([(int)$_GET['cancelar']]);
    redirectPublic('citas.php');
}

http_response_code(405);
echo 'Método no permitido';
?>
