<?php
require_once "config.php";
require_once "middleware.php";

header("Content-Type: application/json");

verificarSesion();
verificarRol(["empleado","administrador","secretario"]);

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $id_paciente = isset($_GET["id_paciente"]) ? (int)$_GET["id_paciente"] : 0;
    if ($id_paciente <= 0) {
        http_response_code(400);
        echo json_encode(["success"=>false,"message"=>"id_paciente requerido"]);
        exit;
    }

    // usa la VISTA como pide tu SQL
    $stmt = $pdo->prepare("
        SELECT numero_diente, estado, descripcion
        FROM vista_odontograma
        WHERE id_paciente = ?
    ");
    $stmt->execute([$id_paciente]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success"=>true,"data"=>$rows]);
    exit;
}

if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $id_historial = (int)($data["id_historial"] ?? 0);
    $numero_diente = (int)($data["numero_diente"] ?? 0);
    $estado = $data["estado"] ?? "";
    $descripcion = trim($data["descripcion"] ?? "");

    if ($id_historial <= 0 || $numero_diente < 1 || $numero_diente > 32 || $estado === "") {
        http_response_code(400);
        echo json_encode(["success"=>false,"message"=>"Datos inválidos"]);
        exit;
    }

    // Nota: tu tabla NO tiene unique para (id_historial, numero_diente),
    // así que hacemos UPSERT manual: si existe -> UPDATE, si no -> INSERT.
    $check = $pdo->prepare("SELECT id_diente FROM dientes_historial WHERE id_historial=? AND numero_diente=? LIMIT 1");
    $check->execute([$id_historial, $numero_diente]);
    $existe = $check->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        $upd = $pdo->prepare("
            UPDATE dientes_historial
            SET estado = ?, descripcion = ?
            WHERE id_diente = ?
        ");
        $upd->execute([$estado, $descripcion, $existe["id_diente"]]);
    } else {
        $ins = $pdo->prepare("
            INSERT INTO dientes_historial (id_historial, numero_diente, estado, descripcion)
            VALUES (?,?,?,?)
        ");
        $ins->execute([$id_historial, $numero_diente, $estado, $descripcion]);
    }

    echo json_encode(["success"=>true]);
    exit;
}

http_response_code(405);
echo json_encode(["success"=>false,"message"=>"Método no permitido"]);