<?php
require 'middleware.php';
validarSesion('secretario');

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare(
  "INSERT INTO pagos (id_cita, monto, metodo_pago, fecha_pago)
   VALUES (?,?,?,CURDATE())"
);

$stmt->execute([
    $data['id_cita'],
    $data['monto'],
    $data['metodo_pago']
]);

echo json_encode(["success"=>true]);