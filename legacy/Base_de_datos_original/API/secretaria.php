<?php
require '../config/database.php';
require '../middleware/auth.php';

validarSesion('secretaria');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM vista_citas_solicitadas");
    echo json_encode($stmt->fetchAll());
}

if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare(
        "UPDATE citas SET estado='confirmada' WHERE id_cita=?"
    );
    $stmt->execute([$data['id_cita']]);

    echo json_encode(["success"=>true]);
}