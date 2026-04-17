<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/config/app.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
require_once __DIR__ . '/../api/middleware/auth.php';

requireLogin(['doctor', 'secretaria']);
ensureProfessionalModules($pdo);

if (!($pdo instanceof PDO)) {
    die('Error de conexión a la base de datos.');
}

$citaId = (int)($_GET['cita_id'] ?? $_POST['cita_id'] ?? 0);
if ($citaId <= 0) {
    header('Location: citas.php');
    exit;
}

$stmt = $pdo->prepare("SELECT c.*, p.nombre AS paciente_nombre, p.id AS paciente_id_ref
                       FROM citas c
                       INNER JOIN pacientes p ON p.id = c.paciente_id
                       WHERE c.id = ?
                       LIMIT 1");
$stmt->execute([$citaId]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    header('Location: citas.php');
    exit;
}

$doctorNombre = '';
$doctorField = null;
try {
    require_once __DIR__ . '/../api/helpers/schema.php';
    $doctorField = obtenerCampoDoctorCitas($pdo);
    if ($doctorField && isset($cita[$doctorField])) {
        $doctorNombre = obtenerNombreDoctor($pdo, (string)$cita[$doctorField]) ?: '';
    }
} catch (Throwable $e) {
    $doctorNombre = '';
}

$cuenta = pmResumenCuentaCita($pdo, $citaId);
$titulo = 'Editar cita';
$subtitulo = 'Actualiza fecha, hora y cambia el estado de la atención';
$active = 'citas';
$extra_css = ['css/modulos.css', 'css/citas.css'];

include '_layout_top.php';
?>

<section class="panel cita-detail-panel">
  <div class="toolbar cita-detail-toolbar">
    <div>
      <h2>Editar cita</h2>
      <p class="muted">Modifica la fecha y hora o cambia el estado desde esta vista.</p>
    </div>
    <a class="btn-secondary" href="citas.php">Volver a citas</a>
  </div>

  <?php if (isset($_GET['actualizada'])): ?>
    <div class="notice success">La fecha y hora de la cita se actualizaron correctamente.</div>
  <?php endif; ?>

  <?php if (isset($_GET['estado'])): ?>
    <div class="notice success">El estado de la cita se actualizó correctamente.</div>
  <?php endif; ?>

  <?php if (isset($_GET['choque'])): ?>
    <div class="notice error">Ya existe una cita del doctor en la fecha y hora seleccionadas.</div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="notice error">No fue posible actualizar la cita. Revisa los datos e intenta de nuevo.</div>
  <?php endif; ?>

  <div class="cita-detail-grid">
    <div class="card-lite">
      <span class="detail-label">Paciente</span>
      <strong><?php echo htmlspecialchars($cita['paciente_nombre'] ?? ''); ?></strong>
    </div>
    <div class="card-lite">
      <span class="detail-label">Doctor</span>
      <strong><?php echo htmlspecialchars($doctorNombre !== '' ? $doctorNombre : 'No disponible'); ?></strong>
    </div>
    <div class="card-lite">
      <span class="detail-label">Motivo</span>
      <strong><?php echo htmlspecialchars($cita['motivo_consulta'] ?? 'Sin motivo'); ?></strong>
    </div>
    <div class="card-lite">
      <span class="detail-label">Estado actual</span>
      <span class="badge <?php echo htmlspecialchars(strtolower((string)($cita['estado'] ?? 'pendiente'))); ?>"><?php echo htmlspecialchars(ucfirst((string)($cita['estado'] ?? 'pendiente'))); ?></span>
    </div>
    <div class="card-lite">
      <span class="detail-label">Total</span>
      <strong><?php echo htmlspecialchars(pmFormatoMoneda((float)($cuenta['total'] ?? 0))); ?></strong>
    </div>
    <div class="card-lite">
      <span class="detail-label">Saldo</span>
      <strong><?php echo htmlspecialchars(pmFormatoMoneda((float)($cuenta['saldo'] ?? 0))); ?></strong>
    </div>
  </div>
</section>

<section class="panel cita-edit-sections">
  <div class="split-panels">
    <div class="panel-sub">
      <h2>Fecha y hora</h2>
      <form class="form-cita-edit" method="POST" action="<?php echo htmlspecialchars(appApiUrl('citas.php')); ?>">
        <input type="hidden" name="action" value="actualizar">
        <input type="hidden" name="cita_id" value="<?php echo $citaId; ?>">
        <input type="hidden" name="paciente_id" value="<?php echo (int)($cita['paciente_id_ref'] ?? $cita['paciente_id'] ?? 0); ?>">
        <input type="hidden" name="redirect_to" value="citas_paciente.php?cita_id=<?php echo $citaId; ?>">

        <div class="field">
          <label for="fecha">Fecha</label>
          <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars((string)($cita['fecha'] ?? '')); ?>" min="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="field">
          <label for="hora">Hora</label>
          <input type="time" id="hora" name="hora" value="<?php echo htmlspecialchars(substr((string)($cita['hora'] ?? ''), 0, 5)); ?>" required>
        </div>

        <div class="field field-wide form-actions-right">
          <button class="btn" type="submit">Guardar cambios</button>
        </div>
      </form>
    </div>

    <div class="panel-sub">
      <h2>Estado de la cita</h2>
      <div class="state-actions-grid">
        <form method="POST" action="<?php echo htmlspecialchars(appApiUrl('citas.php')); ?>">
          <input type="hidden" name="action" value="estado">
          <input type="hidden" name="cita_id" value="<?php echo $citaId; ?>">
          <input type="hidden" name="estado" value="confirmada">
          <input type="hidden" name="redirect_to" value="citas_paciente.php?cita_id=<?php echo $citaId; ?>&estado=1">
          <button type="submit">Confirmar</button>
        </form>

        <form method="POST" action="<?php echo htmlspecialchars(appApiUrl('citas.php')); ?>">
          <input type="hidden" name="action" value="estado">
          <input type="hidden" name="cita_id" value="<?php echo $citaId; ?>">
          <input type="hidden" name="estado" value="atendida">
          <input type="hidden" name="redirect_to" value="citas_paciente.php?cita_id=<?php echo $citaId; ?>&estado=1">
          <button type="submit">Atendida</button>
        </form>

        <form method="POST" action="<?php echo htmlspecialchars(appApiUrl('citas.php')); ?>" onsubmit="return confirm('¿Cancelar esta cita?');">
          <input type="hidden" name="action" value="estado">
          <input type="hidden" name="cita_id" value="<?php echo $citaId; ?>">
          <input type="hidden" name="estado" value="cancelada">
          <input type="hidden" name="redirect_to" value="citas_paciente.php?cita_id=<?php echo $citaId; ?>&estado=1">
          <button type="submit" class="btn-danger">Cancelar</button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
