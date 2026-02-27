<?php
require '../config/database.php';
require '../middleware/auth.php';

validarSesion('secretaria');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

/* ===============================
   1️⃣ OBTENER CITAS SOLICITADAS
   =============================== */
if ($method === 'GET') {

    $stmt = $pdo->query(
        "SELECT * FROM vista_citas_solicitadas"
    );

    echo json_encode($stmt->fetchAll());
    exit;
}

/* ===============================
   2️⃣ CONFIRMAR / CANCELAR CITA
   =============================== */
if ($method === 'PUT') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id_cita']) || !isset($data['estado'])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos"]);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE citas SET estado = ? WHERE id_cita = ?"
    );

    $stmt->execute([
        $data['estado'], // confirmada o cancelada
        $data['id_cita']
    ]);

    echo json_encode(["success" => true]);
    exit;
}

/* ===============================
   MÉTODO NO PERMITIDO
   =============================== */
http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);