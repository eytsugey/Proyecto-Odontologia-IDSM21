<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES (?,?,?,?,?)');
    $stmt->execute([
        trim($_POST['nombre'] ?? ''),
        $_POST['sexo'] ?? null,
        (int)($_POST['edad'] ?? 0) ?: null,
        $_POST['fecha_nacimiento'] ?: null,
        trim($_POST['telefono'] ?? '')
    ]);
    header('Location: /proyecto_odontologia_funcional/public/pacientes.php?ok=1');
    exit;
}
http_response_code(405);
echo 'Método no permitido';
?>
