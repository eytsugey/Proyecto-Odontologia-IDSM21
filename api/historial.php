<?php
require '../config/database.php';
require '../middleware/auth.php';

validarSesion(['admin','odontologo','secretaria']);

header('Content-Type: application/json');

$rol = $_SESSION['usuario']['rol'];
$method = $_SERVER['REQUEST_METHOD'];

/* ===============================
   1️⃣ VER HISTORIAL
   =============================== */
if ($method === 'GET') {

    if (!isset($_GET['id_paciente'])) {
        http_response_code(400);
        echo json_encode(["error" => "Falta id_paciente"]);
        exit;
    }

    $id_paciente = $_GET['id_paciente'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM historial_clinico
        WHERE id_paciente = ?
    ");

    $stmt->execute([$id_paciente]);

    $historial = $stmt->fetch(PDO::FETCH_ASSOC);

    /* SECRETARIA SOLO VE DATOS LIMITADOS */
    if ($rol === 'secretaria' && $historial) {

        $historial = [
            "motivo_consulta" => $historial['motivo_consulta'],
            "fecha" => $historial['fecha']
        ];
    }

    echo json_encode($historial);
    exit;
}

/* ===============================
   2️⃣ CREAR HISTORIAL
   SOLO ADMIN Y ODONTOLOGO
   =============================== */

if ($method === 'POST') {

    if (!in_array($rol, ['admin','odontologo'])) {
        http_response_code(403);
        echo json_encode(["error" => "No autorizado"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare("
        INSERT INTO historial_clinico
        (id_paciente, motivo_consulta, diagnostico, tratamiento)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['id_paciente'],
        $data['motivo_consulta'],
        $data['diagnostico'],
        $data['tratamiento']
    ]);

    echo json_encode(["success" => true]);
    exit;
}

/* ===============================
   3️⃣ ACTUALIZAR HISTORIAL
   =============================== */

if ($method === 'PUT') {

    if (!in_array($rol, ['admin','odontologo'])) {
        http_response_code(403);
        echo json_encode(["error" => "No autorizado"]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare("
        UPDATE historial_clinico
        SET motivo_consulta = ?, diagnostico = ?, tratamiento = ?
        WHERE id_historial = ?
    ");

    $stmt->execute([
        $data['motivo_consulta'],
        $data['diagnostico'],
        $data['tratamiento'],
        $data['id_historial']
    ]);

    echo json_encode(["success" => true]);
    exit;
}

/* ===============================
   MÉTODO NO PERMITIDO
   =============================== */

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);