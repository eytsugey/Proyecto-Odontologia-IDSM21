<?php
require 'middleware.php';
validarSesion('secretario');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {

    // ============================
    // 1️⃣ OBTENER CITAS SOLICITADAS
    // ============================
    if ($method === 'GET') {

        $stmt = $pdo->prepare(
            "SELECT c.id_cita, p.nombre, p.apellido, c.fecha, c.hora
             FROM citas c
             JOIN pacientes p ON c.id_paciente = p.id_paciente
             WHERE c.estado = 'solicitada'"
        );

        $stmt->execute();

        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    // ============================
    // 2️⃣ CONFIRMAR CITA
    // ============================
    if ($method === 'PUT') {

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare(
            "UPDATE citas SET estado = 'confirmada'
             WHERE id_cita = ?"
        );

        $stmt->execute([$data['id_cita']]);

        echo json_encode(["success" => true]);
        exit;
    }

    // ============================
    // 3️⃣ CANCELAR CITA
    // ============================
    if ($method === 'DELETE') {

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $pdo->prepare(
            "UPDATE citas SET estado = 'cancelada'
             WHERE id_cita = ?"
        );

        $stmt->execute([$data['id_cita']]);

        echo json_encode(["success" => true]);
        exit;
    }

    // Método no permitido
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error del servidor"]);
}