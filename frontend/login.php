<?php
session_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Sistema Odontológico</title>
<link rel="stylesheet" href="public/css/styles.css">
<link rel="stylesheet" href="public/css/login.css">
</head>
<body>

<div class="login-container">
    
    <div class="logo">🦷</div>
    <h2>Sistema Odontológico</h2>

    <?php if(isset($_GET['error'])): ?>
        <p style="color:red; text-align:center;">Correo o contraseña incorrectos</p>
    <?php endif; ?>

    <form action="validar_login.php" method="POST">
        <div class="form-group">
            <label>Correo</label>
            <input type="email" name="email" placeholder="doctor@clinica.com" required>
        </div>

        <div class="form-group">
            <label>Contraseña</label>
            <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn">Iniciar sesión</button>

        <div class="extra">
            <a href="#">¿Olvidaste tu contraseña?</a>
        </div>
    </form>

</div>

</body>
</html>