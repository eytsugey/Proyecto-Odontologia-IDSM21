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
$subtitulo = 'Selecciona un diente y cambia el estado. El color se actualiza al instante.';
$active = 'pacientes';
$extra_css = ['css/odontograma.css'];

include '_layout_top.php';
?>

<section class="odontograma-vista">
  <div class="odontograma-header">
    <div>
      <h2 class="odontograma-paciente">
        Paciente: <?php echo htmlspecialchars($paciente['nombre']); ?>
      </h2>
    </div>

    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <div class="odontograma-card">
    <div class="odontograma-grid" id="odontogramaGrid"></div>

    <div class="odontograma-formulario">
      <div class="campo-sm">
        <label for="numeroDiente">ID Historial:</label>
        <input type="number" id="numeroDiente" readonly>
      </div>

      <div class="campo-sm">
        <label for="estadoDiente">Estado:</label>
        <select id="estadoDiente">
          <option value="sano">Sano</option>
          <option value="caries">Caries</option>
          <option value="restaurado">Restaurado</option>
          <option value="extraido">Extraído</option>
          <option value="fracturado">Fracturado</option>
          <option value="endodoncia">Endodoncia</option>
        </select>
      </div>

      <div class="campo-lg">
        <label for="descripcionDiente">Descripción:</label>
        <textarea id="descripcionDiente" rows="3" placeholder="Notas del diente..."></textarea>
      </div>

      <div class="odontograma-botones">
        <button type="button" class="btn" onclick="guardarDienteBD()">Guardar</button>
        <button type="button" class="btn-secondary" onclick="recargarOdontograma()">Recargar</button>
      </div>
    </div>
  </div>
</section>

<script>
window.pacienteActual = <?php echo (int)$pacienteId; ?>;
</script>
<script src="js/app_odontograma_bd.js"></script>

<?php include '_layout_bottom.php'; ?>