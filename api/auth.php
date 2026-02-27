<?php
session_start();

require __DIR__ . '/../config/database.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare(
    "SELECT id_usuario, nombre, password, rol 
     FROM usuarios 
     WHERE email = ? AND activo = 1"
);
$stmt->execute([$email]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || !password_verify($password, $usuario['password'])) {
    http_response_code(401);
    echo json_encode(["error" => "Credenciales incorrectas"]);
    exit;
}

$_SESSION['usuario'] = [
    "id"     => $usuario['id_usuario'],
    "nombre"=> $usuario['nombre'],
    "rol"   => $usuario['rol']
];

echo json_encode([
    "success" => true,
    "rol" => $usuario['rol']
]);