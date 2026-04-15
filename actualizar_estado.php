<?php
require_once __DIR__ . '/../api/config/database.php';

header('Content-Type: application/json');

// Leer datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if(!$data){
    echo json_encode(["ok"=>false,"msg"=>"Sin datos"]);
    exit;
}

$id = $data['id'] ?? null;
$estado = $data['estado'] ?? null;

if(!$id || !$estado){
    echo json_encode(["ok"=>false,"msg"=>"Datos incompletos"]);
    exit;
}

try{

    $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    echo json_encode(["ok"=>true]);

}catch(Exception $e){
    echo json_encode([
        "ok"=>false,
        "error"=>$e->getMessage()
    ]);
}