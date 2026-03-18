<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
if (!$pacienteId) {
    header('Location: pacientes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch();

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$hoy = date('Y-m-d');

$stmt = $pdo->prepare('SELECT * FROM citas WHERE paciente_id = ? AND fecha < ? ORDER BY fecha DESC, hora DESC');
$stmt->execute([$pacienteId, $hoy]);
$citasRealizadas = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM citas WHERE paciente_id = ? AND fecha >= ? ORDER BY fecha ASC, hora ASC');
$stmt->execute([$pacienteId, $hoy]);
$citasProximas = $stmt->fetchAll();

$titulo = 'Citas del paciente';
$subtitulo = 'Historial y próximas citas de ' . $paciente['nombre'];
$active = 'pacientes';

include '_layout_top.php';
?>

<section class="panel">
  <div class="toolbar">
    <h2><?php echo htmlspecialchars($paciente['nombre']); ?></h2>
    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <?php if (isset($_GET['actualizada'])): ?>
    <div class="notice success" style="margin-top:12px;">La fecha y hora de la cita fueron actualizadas.</div>
  <?php endif; ?>

  <?php if (isset($_GET['choque'])): ?>
    <div class="notice error" style="margin-top:12px;">Ya existe una cita del doctor en la fecha y hora seleccionadas.</div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="notice error" style="margin-top:12px;">Completa la nueva fecha y hora para actualizar la cita.</div>
  <?php endif; ?>

  <div class="cards" style="grid-template-columns:1fr 1fr; margin-top:8px;">
    <div class="card">
      <h3>Próximas citas</h3>
      <?php if (!$citasProximas): ?>
        <p>No tiene citas próximas.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Motivo</th>
              <th>Estado</th>
              <th>Editar</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($citasProximas as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['fecha']); ?></td>
                <td><?php echo htmlspecialchars(substr($c['hora'],0,5)); ?></td>
                <td><?php echo htmlspecialchars($c['motivo_consulta']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($c['estado']); ?>"><?php echo htmlspecialchars($c['estado']); ?></span></td>
                <td>
                  <form action="../api/citas.php?action=actualizar" method="POST" style="display:grid; gap:6px; min-width:170px;">
                    <input type="hidden" name="cita_id" value="<?php echo (int)$c['id']; ?>">
                    <input type="hidden" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">
                    <input type="date" name="fecha" value="<?php echo htmlspecialchars($c['fecha']); ?>" min="<?php echo $hoy; ?>" required>
                    <input type="time" name="hora" value="<?php echo htmlspecialchars(substr($c['hora'],0,5)); ?>" required>
                    <button class="btn" type="submit">Guardar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Citas realizadas / pasadas</h3>
      <?php if (!$citasRealizadas): ?>
        <p>No tiene citas pasadas.</p>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Motivo</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($citasRealizadas as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['fecha']); ?></td>
                <td><?php echo htmlspecialchars(substr($c['hora'],0,5)); ?></td>
                <td><?php echo htmlspecialchars($c['motivo_consulta']); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($c['estado']); ?>"><?php echo htmlspecialchars($c['estado']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
