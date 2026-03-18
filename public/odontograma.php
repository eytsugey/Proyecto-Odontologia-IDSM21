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

$titulo = 'Odontograma';
$subtitulo = 'Odontograma guardado en base de datos para ' . $paciente['nombre'];
$active = 'pacientes';

$extra_css = ['css/odontograma.css'];

include '_layout_top.php';
?>

<section class="panel odontograma-panel">
  <div class="toolbar odontograma-toolbar">
    <div>
      <h2><?php echo htmlspecialchars($paciente['nombre']); ?></h2>
      <p class="odontograma-subinfo">Selecciona un diente para registrar su estado clínico y observaciones.</p>
    </div>
    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <div class="odontograma-leyenda">
    <span class="badge-estado sano">Sano</span>
    <span class="badge-estado caries">Caries</span>
    <span class="badge-estado restaurado">Restaurado</span>
    <span class="badge-estado extraido">Extraído</span>
    <span class="badge-estado fracturado">Fracturado</span>
    <span class="badge-estado endodoncia">Endodoncia</span>
  </div>

  <div class="odontograma-board">
    <div class="arcada-box">
      <h3>Arcada superior</h3>
      <div class="arcada-scroll">
        <div class="arcada superior" id="arcadaSuperior"></div>
      </div>
    </div>

    <div class="arcada-box">
      <h3>Arcada inferior</h3>
      <div class="arcada-scroll">
        <div class="arcada inferior" id="arcadaInferior"></div>
      </div>
    </div>
  </div>

  <div class="odontograma-divider"></div>

  <div class="odontograma-form-card">
    <h3>Detalle del diente</h3>

    <div class="form-grid odontograma-form-grid">
      <div class="field">
        <label for="numeroDiente">Número de diente</label>
        <input type="number" id="numeroDiente" readonly>
      </div>

      <div class="field">
        <label for="estadoDiente">Estado</label>
        <select id="estadoDiente">
          <option value="sano">Sano</option>
          <option value="caries">Caries</option>
          <option value="restaurado">Restaurado</option>
          <option value="extraido">Extraído</option>
          <option value="fracturado">Fracturado</option>
          <option value="endodoncia">Endodoncia</option>
        </select>
      </div>

      <div class="field field-wide">
        <label for="descripcionDiente">Descripción</label>
        <textarea id="descripcionDiente" rows="4" placeholder="Observaciones del diente..."></textarea>
      </div>

      <div class="field field-wide odontograma-actions">
        <button type="button" class="btn" onclick="guardarDienteBD()">Guardar diente</button>
      </div>
    </div>
  </div>
</section>

<script>
window.pacienteActual = <?php echo (int)$pacienteId; ?>;
</script>
<script src="js/app_odontograma_bd.js"></script>

<?php include '_layout_bottom.php'; ?>