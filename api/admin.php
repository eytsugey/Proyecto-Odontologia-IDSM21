<?php
require 'middleware.php';
validarSesion('administrador');

header('Content-Type: application/json');

$stmt = $pdo->query(
    "SELECT 
        metodo_pago,
        SUM(total_ingresos) AS total
     FROM vista_finanzas_semanal
     WHERE anio = YEAR(CURDATE())
     GROUP BY metodo_pago"
);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));