<?php
require '../config/database.php';
require '../middleware/auth.php';

validarSesion('odontologo');

$stmt = $pdo->query("SELECT * FROM vista_historia_resumen");
echo json_encode($stmt->fetchAll());