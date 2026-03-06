<?php
header("Content-Type: application/json");
require_once "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['nombre'], $data['email'], $data['password'], $data['rol'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$nombre = trim($data['nombre']);
$email = trim($data['email']);
$password = trim($data['password']);
$rol = trim($data['rol']);

try {

    // verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(["error" => "El correo ya está registrado"]);
        exit;
    }

    // encriptar contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // insertar usuario
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre,email,password,rol,activo) VALUES (?,?,?,?,1)");
    $stmt->execute([$nombre,$email,$hash,$rol]);

    echo json_encode([
        "mensaje" => "Usuario registrado correctamente"
    ]);

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode([
        "error" => "Error en el servidor"
    ]);
}