<?php
$ok = isset($_GET['ok']);
$error = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agendar cita</title>
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/citas.css">
  <link rel="stylesheet" href="css/responsive.css">
  <link rel="stylesheet" href="css/agendar_cita.css">
</head>
<body class="page-cita">
  <header class="public-topbar">
    <div class="topbar-text">
      <h1>Solicitud de cita</h1>
      <p>Agenda tu atención dental de forma rápida y sencilla.</p>
    </div>
    <a class="public-link" href="login.php">Volver al sistema</a>
  </header>

  <main class="public-main">
    <section class="panel form-panel">
      <?php if ($ok): ?>
        <div class="notice success">
          Solicitud enviada correctamente. La secretaria registrará el resto de la historia clínica.
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="notice error">
          Completa todos los datos requeridos.
        </div>
      <?php endif; ?>

      <div class="cita-hero">
        <div class="cita-icon">🦷</div>
        <div>
          <h2>Agenda tu cita</h2>
          <p>Llena el formulario con tus datos y una fecha preferida. Nos ayudará a organizar tu atención de manera más rápida.</p>
        </div>
      </div>

      <form action="../api/citas.php?action=solicitar" method="POST" class="cita-form">
        <div class="form-grid">
          <div class="field field-wide">
            <label for="nombre">Nombre</label>
            <input id="nombre" type="text" name="nombre" required>
          </div>

          <div class="field">
            <label for="sexo">Sexo</label>
            <select id="sexo" name="sexo" required>
              <option value="">Selecciona</option>
              <option value="Masculino">Masculino</option>
              <option value="Femenino">Femenino</option>
              <option value="Otro">Otro</option>
            </select>
          </div>

          <div class="field">
            <label for="edad">Edad</label>
            <input id="edad" type="number" name="edad" min="0" max="120" required>
          </div>

          <div class="field">
            <label for="fecha_nacimiento">Fecha de nacimiento</label>
            <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" required>
          </div>

          <div class="field">
            <label for="telefono">Teléfono celular</label>
            <input id="telefono" type="text" name="telefono" required>
          </div>

          <div class="field">
            <label for="fecha_preferida">Fecha preferida</label>
            <input id="fecha_preferida" type="date" name="fecha_preferida" required>
          </div>

          <div class="field">
            <label for="hora_preferida">Hora preferida</label>
            <input id="hora_preferida" type="time" name="hora_preferida" required>
          </div>

          <div class="field field-wide">
            <label for="motivo">Motivo de consulta</label>
            <textarea id="motivo" name="motivo" rows="4" required placeholder="Ej. dolor dental, limpieza, revisión general..."></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn-primary" type="submit">Enviar solicitud</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>