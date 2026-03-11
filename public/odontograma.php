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

include '_layout_top.php';
?>

<section class="panel">
  <div class="toolbar">
    <h2><?php echo htmlspecialchars($paciente['nombre']); ?></h2>
    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <div class="odontograma-container" style="margin-top:18px;">
    <div class="arcada superior" id="arcadaSuperior"></div>
    <div class="arcada inferior" id="arcadaInferior"></div>
  </div>

  <hr style="margin:20px 0; border:none; height:1px; background:#e5e7eb;">

  <div class="form-grid">
    <div class="field">
      <label>Número de diente</label>
      <input type="number" id="numeroDiente" readonly>
    </div>

    <div class="field">
      <label>Estado</label>
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
      <label>Descripción</label>
      <textarea id="descripcionDiente" rows="3" placeholder="Observaciones del diente..."></textarea>
    </div>

    <div class="field field-wide">
      <button type="button" class="btn" onclick="guardarDienteBD()">Guardar diente</button>
    </div>
  </div>
</section>

<style>
.odontograma-container{
  display:flex;
  flex-direction:column;
  gap:25px;
}
.arcada{
  display:grid;
  grid-template-columns:repeat(16, minmax(48px, 1fr));
  gap:10px;
}
.diente{
  background:#fff;
  border:2px solid #cbd5e1;
  border-radius:12px;
  padding:12px 6px;
  text-align:center;
  cursor:pointer;
  font-weight:bold;
  transition:.2s ease;
  min-height:70px;
  display:flex;
  align-items:center;
  justify-content:center;
}
.diente:hover{
  transform:translateY(-2px);
  box-shadow:0 8px 18px rgba(0,0,0,.08);
}
.diente.activo{
  outline:3px solid #1e3a8a;
}
.diente.sano{ background:#f8fafc; color:#0f172a; }
.diente.caries{ background:#fee2e2; color:#991b1b; }
.diente.restaurado{ background:#dbeafe; color:#1d4ed8; }
.diente.extraido{ background:#e5e7eb; color:#374151; }
.diente.fracturado{ background:#fef3c7; color:#92400e; }
.diente.endodoncia{ background:#ede9fe; color:#6d28d9; }

@media (max-width:900px){
  .arcada{
    grid-template-columns:repeat(8, minmax(48px, 1fr));
  }
}
</style>

<script>
window.pacienteActual = <?php echo (int)$pacienteId; ?>;
</script>
<script src="js/app_odontograma_bd.js"></script>

<?php include '_layout_bottom.php'; ?>