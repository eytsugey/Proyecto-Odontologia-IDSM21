<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? LIMIT 1');
$stmt->execute([$correo]);
$user = $stmt->fetch();
if ($user && $password === $user['password'])  {
    $_SESSION['usuario_id'] = (int)$user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    header('Location: /proyecto_odontologia_funcional/public/' . ($user['rol'] === 'secretaria' ? 'secretaria.php' : 'admin.php'));
    exit;
}
header('Location: /proyecto_odontologia_funcional/public/login.php?error=1');
?>
