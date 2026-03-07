<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor']);
$titulo='Odontograma'; $subtitulo=''; $active='odontograma';
include '_layout_top.php';
$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre')->fetchAll();
$pacienteId = (int)($_GET['paciente_id'] ?? ($pacientes[0]['id'] ?? 1));
?>
<div class="odontograma-wrap">
  <div class="odontograma-header">
    <h2>Odontograma</h2>
    <div class="odontograma-help">Selecciona un diente y cambia el estado. El color se actualiza al instante.</div>
  </div>
  <div class="toolbar" style="margin:10px 0 16px;">
    <form method="GET" style="display:flex; gap:8px; align-items:center;">
      <select name="paciente_id"><?php foreach($pacientes as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $pacienteId===$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option><?php endforeach; ?></select>
      <button class="btn-secondary" type="submit">Cambiar paciente</button>
    </form>
  </div>
  <section class="odontograma-container">
    <div class="arcada superior" id="arcadaSuperior"></div>
    <div class="arcada inferior" id="arcadaInferior"></div>
  </section>
  <section class="panel-control">
    <label>ID Paciente
      <input type="number" id="idPaciente" value="<?php echo $pacienteId; ?>" readonly>
    </label>
    <label>Estado
      <select id="estadoDiente">
        <option value="sano">Sano</option>
        <option value="caries">Caries</option>
        <option value="restaurado">Restaurado</option>
        <option value="extraido">Extraído</option>
        <option value="fracturado">Fracturado</option>
        <option value="endodoncia">Endodoncia</option>
      </select>
    </label>
    <label>Descripción
      <textarea id="descripcionDiente" rows="2" placeholder="Notas del diente..."></textarea>
    </label>
    <button class="btn" type="button" onclick="guardarDiente()">Guardar</button>
    <button class="btn-secondary" type="button" onclick="cargarOdontograma()">Recargar</button>
  </section>
</div>
<script>
const pacienteActual = <?php echo $pacienteId; ?>;
</script>
<script src="js/app.js"></script>
<?php include '_layout_bottom.php'; ?>
