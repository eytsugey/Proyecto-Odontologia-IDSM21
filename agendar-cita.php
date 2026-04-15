<?php
$error = $_GET['error'] ?? '';

$mensajesError = [
    '1' => 'Completa todos los datos requeridos.',
    'fecha_pasada' => 'La fecha preferida no puede ser anterior al día de hoy.',
    'fecha_nacimiento' => 'La fecha de nacimiento no es válida.',
    'edad_incorrecta' => 'La edad no coincide con la fecha de nacimiento capturada.',
];

$mensajeError = $mensajesError[$error] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Agendar cita</title>
  <link rel="stylesheet" href="css/styles.css">
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
      <?php if ($mensajeError): ?>
        <div class="notice error">
          <?php echo htmlspecialchars($mensajeError); ?>
        </div>
      <?php endif; ?>

      <div class="cita-hero">
        <div class="cita-icon">🦷</div>
        <div>
          <h2>Agenda tu cita</h2>
          <p>Llena el formulario con tus datos y una fecha preferida. Nos ayudará a organizar tu atención de manera más rápida.</p>
        </div>
      </div>

      <form action="../api/citas.php?action=solicitar" method="POST" class="cita-form" id="solicitud-cita-form">
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
            <input id="fecha_preferida" type="date" name="fecha_preferida" min="<?php echo date('Y-m-d'); ?>" required>
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

  <script>
    (function () {
      const form = document.getElementById('solicitud-cita-form');
      if (!form) return;

      form.addEventListener('submit', function (event) {
        const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
        const edad = parseInt(document.getElementById('edad').value, 10);
        const fechaPreferida = document.getElementById('fecha_preferida').value;

        if (fechaPreferida) {
          const hoy = new Date();
          hoy.setHours(0, 0, 0, 0);
          const fechaElegida = new Date(fechaPreferida + 'T00:00:00');

          if (fechaElegida < hoy) {
            event.preventDefault();
            alert('La fecha preferida no puede ser anterior al día de hoy.');
            return;
          }
        }

        if (fechaNacimiento && !Number.isNaN(edad)) {
          const nacimiento = new Date(fechaNacimiento + 'T00:00:00');
          const hoy = new Date();
          let edadCalculada = hoy.getFullYear() - nacimiento.getFullYear();
          const mes = hoy.getMonth() - nacimiento.getMonth();

          if (mes < 0 || (mes === 0 && hoy.getDate() < nacimiento.getDate())) {
            edadCalculada--;
          }

          if (edadCalculada !== edad) {
            event.preventDefault();
            alert('La edad no coincide con la fecha de nacimiento capturada.');
          }
        }
      });
    })();
  </script>
</body>
</html>
