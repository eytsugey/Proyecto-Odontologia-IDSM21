<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor','secretaria']);

$titulo = 'Citas';
$subtitulo = 'Crear, confirmar, editar o cancelar citas';
$active = 'citas';

include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre')->fetchAll();
$doctores = $pdo->query('SELECT cedula, nombre FROM doctores ORDER BY nombre')->fetchAll();

$citas = $pdo->query("
    SELECT
        c.id,
        c.paciente_id,
        c.cedula_doctor,
        p.nombre AS paciente,
        d.nombre AS doctor,
        c.fecha,
        c.hora,
        c.motivo_consulta,
        c.estado
    FROM citas c
    JOIN pacientes p ON p.id = c.paciente_id
    JOIN doctores d ON d.cedula = c.cedula_doctor
    ORDER BY c.fecha DESC, c.hora ASC
")->fetchAll();
?>

<section class="panel">
  <h2>Nueva cita</h2>

  <?php if (isset($_GET['ok'])): ?>
    <div class="notice success">Cita registrada.</div>
  <?php endif; ?>

  <?php if (isset($_GET['choque'])): ?>
    <div class="notice error">Ya existe una cita del doctor a esa hora.</div>
  <?php endif; ?>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'doctor'): ?>
    <div class="notice error">No se encontró un doctor válido para registrar la cita.</div>
  <?php endif; ?>

  <form class="form-grid" action="../api/citas.php" method="POST">
    <div class="field">
      <label>Paciente</label>
      <select name="paciente_id" required>
        <?php foreach ($pacientes as $p): ?>
          <option value="<?php echo $p['id']; ?>">
            <?php echo htmlspecialchars($p['nombre']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Doctor</label>
      <select name="cedula_doctor" required>
        <?php foreach ($doctores as $d): ?>
          <option value="<?php echo htmlspecialchars($d['cedula']); ?>">
            <?php echo htmlspecialchars($d['nombre']); ?> (<?php echo htmlspecialchars($d['cedula']); ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Fecha</label>
      <input type="date" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <div class="field">
      <label>Hora</label>
      <input type="time" name="hora" required>
    </div>

    <div class="field">
      <label>Estado</label>
      <select name="estado">
        <option value="pendiente">Pendiente</option>
        <option value="confirmada">Confirmada</option>
      </select>
    </div>

    <div class="field field-wide">
      <label>Motivo de consulta</label>
      <input type="text" name="motivo_consulta">
    </div>

    <div class="field field-wide">
      <button class="btn" type="submit">Guardar cita</button>
    </div>
  </form>
</section>

<section class="panel">
  <h2>Agenda</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Paciente</th>
        <th>Doctor</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Motivo</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($citas as $c): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['paciente']); ?></td>
          <td><?php echo htmlspecialchars($c['doctor']); ?></td>
          <td><?php echo htmlspecialchars($c['fecha']); ?></td>
          <td><?php echo htmlspecialchars(substr($c['hora'], 0, 5)); ?></td>
          <td><?php echo htmlspecialchars($c['motivo_consulta']); ?></td>
          <td>
            <span class="badge <?php echo htmlspecialchars($c['estado']); ?>">
              <?php echo htmlspecialchars($c['estado']); ?>
            </span>
          </td>
          <td>
            <a href="citas_paciente.php?paciente_id=<?php echo $c['paciente_id']; ?>">Editar fecha/hora</a> |
            <a href="../api/citas.php?confirmar=<?php echo $c['id']; ?>">Confirmar</a> |
            <a href="../api/citas.php?cancelar=<?php echo $c['id']; ?>">Cancelar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php include '_layout_bottom.php'; ?>