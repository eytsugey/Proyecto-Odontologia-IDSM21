<?php
session_start();
require_once __DIR__ . '/config/database.php';
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'solicitar') {
    $stmt = $pdo->prepare('INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES (?,?,?,?,?)');
    $stmt->execute([
        trim($_POST['nombre'] ?? ''),
        $_POST['sexo'] ?? null,
        (int)($_POST['edad'] ?? 0) ?: null,
        $_POST['fecha_nacimiento'] ?: null,
        trim($_POST['telefono'] ?? '')
    ]);
    $pacienteId = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        $pacienteId,
        1,
        $_POST['fecha_preferida'] ?: null,
        $_POST['hora_preferida'] ?: null,
        trim($_POST['motivo'] ?? ''),
        'pendiente'
    ]);
    header('Location: /proyecto_odontologia_funcional/public/agendar-cita.php?ok=1');
    exit;
}
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorId = 1;
    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;
    $check = $pdo->prepare('SELECT COUNT(*) FROM citas WHERE doctor_id = ? AND fecha = ? AND hora = ? AND estado <> "cancelada"');
    $check->execute([$doctorId, $fecha, $hora]);
    if ($check->fetchColumn() > 0) {
        header('Location: /proyecto_odontologia_funcional/public/citas.php?choque=1');
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([
        (int)($_POST['paciente_id'] ?? 0),
        $doctorId,
        $fecha,
        $hora,
        trim($_POST['motivo_consulta'] ?? ''),
        $_POST['estado'] ?? 'confirmada'
    ]);
    header('Location: /proyecto_odontologia_funcional/public/citas.php?ok=1');
    exit;
}
if (isset($_GET['confirmar'])) {
    $pdo->prepare('UPDATE citas SET estado = "confirmada" WHERE id = ?')->execute([(int)$_GET['confirmar']]);
    header('Location: /proyecto_odontologia_funcional/public/citas.php');
    exit;
}
if (isset($_GET['cancelar'])) {
    $pdo->prepare('UPDATE citas SET estado = "cancelada" WHERE id = ?')->execute([(int)$_GET['cancelar']]);
    header('Location: /proyecto_odontologia_funcional/public/citas.php');
    exit;
}
http_response_code(405);
echo 'Método no permitido';
?>
