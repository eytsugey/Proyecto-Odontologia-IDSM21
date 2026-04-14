<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/helpers/schema.php';
require_once __DIR__ . '/../api/middleware/auth.php';

requireLogin(['doctor']);

$titulo = 'Mi agenda';
$subtitulo = 'Citas confirmadas del día';
$active = 'doctor_agenda';
$extra_css = ['css/doctor_agenda.css'];

include '_layout_top.php';

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$mode = citasDoctorMode($pdo);

if ($mode === 'cedula') {
    $sql = "
        SELECT c.id, c.paciente_id, p.nombre AS paciente, c.fecha, c.hora, c.motivo_consulta, LOWER(c.estado) AS estado
        FROM citas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        WHERE LOWER(c.estado) = 'confirmada' AND c.fecha = ?
        ORDER BY c.hora ASC
    ";
} else {
    $sql = "
        SELECT c.id, c.paciente_id, p.nombre AS paciente, c.fecha, c.hora, c.motivo_consulta, LOWER(c.estado) AS estado
        FROM citas c
        INNER JOIN pacientes p ON p.id = c.paciente_id
        WHERE LOWER(c.estado) = 'confirmada' AND c.fecha = ?
        ORDER BY c.hora ASC
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$fecha]);
$citas = $stmt->fetchAll();

$totalCitas = count($citas);
$primeraCita = $totalCitas ? substr($citas[0]['hora'], 0, 5) : '--:--';

$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
$fechaBonita = $fechaObj ? $fechaObj->format('d/m/Y') : $fecha;

$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime('+1 day'));
?>

<section class="panel doctor-agenda-panel">
  <div class="agenda-header-row">
    <div>
      <h2>Agenda del doctor</h2>
      <p class="agenda-text">Consulta rápida de citas confirmadas.</p>
    </div>

    <div class="agenda-actions">
      <a class="mini-btn" href="doctor_agenda.php?fecha=<?php echo $hoy; ?>">Hoy</a>
      <a class="mini-btn" href="doctor_agenda.php?fecha=<?php echo $manana; ?>">Mañana</a>
    </div>
  </div>

  <form class="agenda-filter-row" method="GET">
    <div class="date-field">
      <label for="fecha">Fecha</label>
      <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($fecha); ?>" required>
    </div>

    <button class="btn agenda-btn" type="submit">Ver agenda</button>
  </form>

  <div class="agenda-stats">
    <div class="stat-box">
      <span>Fecha</span>
      <strong><?php echo htmlspecialchars($fechaBonita); ?></strong>
    </div>
    <div class="stat-box">
      <span>Citas</span>
      <strong><?php echo $totalCitas; ?></strong>
    </div>
    <div class="stat-box">
      <span>Primera</span>
      <strong><?php echo htmlspecialchars($primeraCita); ?></strong>
    </div>
  </div>
</section>

<section class="panel doctor-agenda-panel">
  <div class="agenda-list-head">
    <h2>Agenda del <?php echo htmlspecialchars($fechaBonita); ?></h2>
  </div>

  <?php if (empty($citas)): ?>
    <div class="agenda-empty">No hay citas confirmadas para esta fecha.</div>
  <?php else: ?>
    <div class="agenda-compact-list">
      <?php foreach ($citas as $c): ?>
        <div class="agenda-row">
          <div class="agenda-col hora"><?php echo htmlspecialchars(substr($c['hora'], 0, 5)); ?></div>
          <div class="agenda-col paciente"><strong><?php echo htmlspecialchars($c['paciente']); ?></strong><span>ID: #<?php echo htmlspecialchars($c['paciente_id']); ?></span></div>
          <div class="agenda-col motivo"><?php echo htmlspecialchars($c['motivo_consulta'] ?: 'Consulta general'); ?></div>
          <div class="agenda-col estado"><span class="estado-badge">Confirmada</span></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include '_layout_bottom.php'; ?>
