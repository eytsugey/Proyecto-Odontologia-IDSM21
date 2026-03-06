<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare(
    "SELECT id_usuario, nombre, password, rol
     FROM usuarios
     WHERE email=? AND activo=1"
);
$stmt->execute([$data['email']]);
$usuario = $stmt->fetch();

if ($usuario && password_verify($data['password'], $usuario['password'])) {

    $_SESSION['usuario'] = [
        'id' => $usuario['id_usuario'],
        'nombre' => $usuario['nombre'],
        'rol' => $usuario['rol']
    ];

    echo json_encode([
        "success" => true,
        "rol" => $usuario['rol']
    ]);

} else {

    echo json_encode([
        "success" => false,
        "error" => "Correo o contraseña incorrectos"
    ]);
}

