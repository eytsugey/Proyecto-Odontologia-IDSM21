<?php $ok = isset($_GET['ok']); $error = isset($_GET['error']); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agendar cita</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/citas.css">
  <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
  <header class="public-topbar">
    <div>
      <h1>Solicitud de cita</h1>
    </div>
    <a class="public-link" href="login.php">Volver al sistema</a>
  </header>
  <main class="public-main">
    <section class="panel form">
      <?php if ($ok): ?><div class="notice success">Solicitud enviada. La secretaria registrará el resto de la historia clínica.</div><?php endif; ?>
      <?php if ($error): ?><div class="notice error">Completa los datos requeridos.</div><?php endif; ?>
      <form action="../api/citas.php?action=solicitar" method="POST">
        <div class="form-grid">
          <div class="field field-wide"><label>Nombre</label><input type="text" name="nombre" required></div>
          <div class="field"><label>Sexo</label><select name="sexo" required><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div>
          <div class="field"><label>Edad</label><input type="number" name="edad" min="0" max="120" required></div>
          <div class="field"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" required></div>
          <div class="field"><label>Teléfono celular</label><input type="text" name="telefono" required></div>
          <div class="field"><label>Fecha preferida</label><input type="date" name="fecha_preferida" required></div>
          <div class="field"><label>Hora preferida</label><input type="time" name="hora_preferida" required></div>
          <div class="field field-wide"><label>Motivo de consulta</label><textarea name="motivo" rows="3" required></textarea></div>
        </div>
        <div class="form-actions">
          <button class="btn-primary" type="submit">Enviar solicitud</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
