<?php
session_start();
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ' . ($_SESSION['rol'] === 'secretaria' ? 'secretaria.php' : 'admin.php'));
    exit;
}
$error = isset($_GET['error']);
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Sistema Odontológico</title>
<link rel="stylesheet" href="css/login.css">
<link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="login-container">
    <div class="logo">🦷</div>
    <h2>Sistema Odontológico</h2>
    <?php if ($error): ?><div class="notice error" style="margin-bottom:14px;">Correo o contraseña incorrectos.</div><?php endif; ?>
    <form action="../api/auth/login.php" method="POST">
        <div class="form-group">
            <label>Correo</label>
            <input type="email" name="correo" required>
        </div>
        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn" type="submit">Entrar</button>
    </form>
    <div class="extra"><a href="agendar-cita.php">Solicitar una cita</a></div>
</div>
</body>
</html>
