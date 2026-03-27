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

function obtenerCedulaDoctorPorDefecto(PDO $pdo): ?string {
    $stmt = $pdo->query("SELECT cedula FROM doctores ORDER BY nombre ASC LIMIT 1");
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    return $doctor['cedula'] ?? null;
}

function validarChoqueCita(PDO $pdo, string $cedulaDoctor, ?string $fecha, ?string $hora, int $ignorarId = 0): bool {
    $sql = 'SELECT COUNT(*) FROM citas WHERE cedula_doctor = ? AND fecha = ? AND hora = ? AND estado <> "cancelada"';
    $params = [$cedulaDoctor, $fecha, $hora];

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

    $cedulaDoctor = obtenerCedulaDoctorPorDefecto($pdo);
    if (!$cedulaDoctor) {
        redirectPublic('agendar-cita.php?error=sin_doctor');
    }

    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, cedula_doctor, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $pacienteId,
        $cedulaDoctor,
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
    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;

    if (!$citaId || !$pacienteId || !$fecha || !$hora) {
        redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&error=datos');
    }

    $stmt = $pdo->prepare('SELECT cedula_doctor FROM citas WHERE id = ? LIMIT 1');
    $stmt->execute([$citaId]);
    $citaActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$citaActual || empty($citaActual['cedula_doctor'])) {
        redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&error=doctor');
    }

    $cedulaDoctor = $citaActual['cedula_doctor'];

    if (validarChoqueCita($pdo, $cedulaDoctor, $fecha, $hora, $citaId)) {
        redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&choque=1');
    }

    $stmt = $pdo->prepare('UPDATE citas SET fecha = ?, hora = ? WHERE id = ?');
    $stmt->execute([$fecha, $hora, $citaId]);

    redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&actualizada=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedulaDoctor = trim($_POST['cedula_doctor'] ?? '');

    if ($cedulaDoctor === '') {
        $cedulaDoctor = obtenerCedulaDoctorPorDefecto($pdo) ?? '';
    }

    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;

    if ($cedulaDoctor === '') {
        redirectPublic('citas.php?error=doctor');
    }

    if (validarChoqueCita($pdo, $cedulaDoctor, $fecha, $hora)) {
        redirectPublic('citas.php?choque=1');
    }

    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, cedula_doctor, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        (int)($_POST['paciente_id'] ?? 0),
        $cedulaDoctor,
        $fecha,
        $hora,
        trim($_POST['motivo_consulta'] ?? ''),
        $_POST['estado'] ?? 'pendiente',
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