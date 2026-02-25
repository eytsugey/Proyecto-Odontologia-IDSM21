<?php
require 'middleware.php';
validarSesion('secretario');

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare(
  "INSERT INTO pacientes (nombre, apellido, telefono, fecha_nacimiento)
   VALUES (?,?,?,?)"
);

$stmt->execute([
    $data['nombre'],
    $data['apellido'],
    $data['telefono'],
    $data['fecha_nacimiento']
]);

echo json_encode(["success"=>true]);