<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $pacienteId = (int)($_GET['paciente_id'] ?? 0);

    if (!$pacienteId) {
        http_response_code(400);
        echo json_encode(['error' => 'paciente_id requerido']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT diente, estado, descripcion
        FROM odontograma_paciente
        WHERE paciente_id = ?
        ORDER BY diente
    ");
    $stmt->execute([$pacienteId]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data = [];

    foreach ($rows as $row) {
        $data[$row['diente']] = [
            'estado' => $row['estado'],
            'descripcion' => $row['descripcion']
        ];
    }

    echo json_encode($data);
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'JSON inválido',
            'raw' => $raw
        ]);
        exit;
    }

    $pacienteId = (int)($input['paciente_id'] ?? 0);
    $diente = trim($input['diente'] ?? '');
    $estado = trim($input['estado'] ?? '');
    $descripcion = trim($input['descripcion'] ?? '');

    if (!$pacienteId || $diente === '' || $estado === '') {
        http_response_code(400);
        echo json_encode([
            'error' => 'Datos incompletos',
            'recibido' => $input
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO odontograma_paciente (paciente_id, diente, estado, descripcion)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            estado = VALUES(estado),
            descripcion = VALUES(descripcion)
    ");
    $stmt->execute([$pacienteId, $diente, $estado, $descripcion]);

    echo json_encode([
        'ok' => true,
        'message' => 'Diente guardado',
        'paciente_id' => $pacienteId,
        'diente' => $diente
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);