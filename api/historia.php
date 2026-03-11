<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);
    $exists = $pdo->prepare('SELECT id FROM historias_clinicas WHERE paciente_id = ? LIMIT 1');
    $exists->execute([$pacienteId]);
    $row = $exists->fetch();
    $data = [
        trim($_POST['alergias'] ?? ''), trim($_POST['cirugias'] ?? ''), trim($_POST['diabetes'] ?? ''),
        trim($_POST['presion_alta'] ?? ''), trim($_POST['presion_baja'] ?? ''), trim($_POST['medicamentos'] ?? ''),
        trim($_POST['habitos'] ?? ''), trim($_POST['motivo_consulta'] ?? '')
    ];
    if ($row) {
        $stmt = $pdo->prepare('UPDATE historias_clinicas SET alergias=?, cirugias=?, diabetes=?, presion_alta=?, presion_baja=?, medicamentos=?, habitos=?, motivo_consulta=? WHERE paciente_id=?');
        $data[] = $pacienteId;
        $stmt->execute($data);
    } else {
        $stmt = $pdo->prepare('INSERT INTO historias_clinicas (alergias, cirugias, diabetes, presion_alta, presion_baja, medicamentos, habitos, motivo_consulta, paciente_id) VALUES (?,?,?,?,?,?,?,?,?)');
        $data[] = $pacienteId;
        $stmt->execute($data);
    }
    header('Location: /Proyecto-Odontologia-IDSM21/public/historia.php?paciente_id=' . $pacienteId . '&ok=1');
    exit;
}
http_response_code(405);
?>
