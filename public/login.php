<?php
session_start();

if (!empty($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'secretaria') {
        header('Location: secretaria.php');
    } elseif ($_SESSION['rol'] === 'doctor') {
        header('Location: doctor_agenda.php');
    } else {
        header('Location: admin.php');
    }
    exit;
}

$error = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DentalApp | Iniciar sesión</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-page">
  <div class="login-card">

    <section class="login-brand">
      <div class="brand-overlay"></div>

      <div class="brand-content">
        <div class="brand-logo">
          <div class="logo-circle">
            <span>🦷</span>
          </div>
        </div>

        <h1>DentalApp</h1>
        <p class="brand-subtitle">Sistema odontológico</p>

        <div class="brand-line"></div>

        <h2>Bienvenido</h2>
        <p class="brand-text">
          Gestiona pacientes, citas y atención clínica de forma ordenada y profesional.
        </p>
      </div>
    </section>

    <section class="login-form-panel">
      <div class="form-box">
        <h3>Iniciar sesión</h3>
        <p class="form-subtitle">Acceso al sistema odontológico</p>

        <?php if ($error): ?>
          <div class="notice-error">
            Correo o contraseña incorrectos.
          </div>
        <?php endif; ?>

        <form action="../api/auth/login.php" method="POST" class="form">
          <div class="form-group">
            <label for="correo">Correo electrónico</label>
            <input type="email" id="correo" name="correo" placeholder="usuario@clinica.com" required>
          </div>

          <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
          </div>

          <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="form-footer">
          <a href="agendar-cita.php">Solicitar una cita</a>
        </div>
      </div>
    </section>

  </div>
</div>

</body>
</html>

