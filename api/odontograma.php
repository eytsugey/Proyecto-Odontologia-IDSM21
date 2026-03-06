<?php
session_start();
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $idPaciente = (int)($_GET['id_paciente'] ?? 0);
    if (!$idPaciente) {
        echo json_encode(['success' => false, 'message' => 'Paciente no válido']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT numero_diente, estado, descripcion FROM odontograma WHERE paciente_id = ? ORDER BY numero_diente');
    $stmt->execute([$idPaciente]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $pacienteId = (int)($data['id_paciente'] ?? 1);
    $numero = (int)($data['numero_diente'] ?? 0);
    $estado = trim($data['estado'] ?? 'sano');
    $descripcion = trim($data['descripcion'] ?? '');
    if (!$pacienteId || !$numero) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO odontograma (paciente_id, numero_diente, estado, descripcion) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE estado=VALUES(estado), descripcion=VALUES(descripcion)');
    $stmt->execute([$pacienteId, $numero, $estado, $descripcion]);
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Método no permitido']);
?>
