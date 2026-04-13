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

$edadPaciente = null;
if (!empty($paciente['fecha_nacimiento'])) {
    try {
        $nacimiento = new DateTime($paciente['fecha_nacimiento']);
        $hoy = new DateTime();
        if ($nacimiento <= $hoy) {
            $edadPaciente = $nacimiento->diff($hoy)->y;
        }
    } catch (Exception $e) {
        $edadPaciente = null;
    }
}

if ($edadPaciente === null && isset($paciente['edad']) && $paciente['edad'] !== '') {
    $edadPaciente = (int)$paciente['edad'];
}

$tipoDenticion = 'permanente';
if ($edadPaciente !== null) {
    if ($edadPaciente <= 5) {
        $tipoDenticion = 'temporal';
    } elseif ($edadPaciente <= 12) {
        $tipoDenticion = 'mixta';
    }
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
      <div class="odontograma-meta">
        <span><strong>Edad:</strong> <?php echo $edadPaciente !== null ? (int)$edadPaciente . ' años' : 'No registrada'; ?></span>
        <span><strong>Dentición:</strong> <?php echo ucfirst($tipoDenticion); ?></span>
      </div>
    </div>

    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <div class="odontograma-card">
    <div class="odontograma-leyenda">
      <span class="badge badge-tipo badge-activo" id="badgeTipoDenticion"></span>
      <span class="badge">Temporal: 55-51 / 61-65 / 85-81 / 71-75</span>
      <span class="badge">Permanente: 18-28 / 48-38</span>
    </div>

    <div class="odontograma-secciones" id="odontogramaSecciones"></div>

    <div class="odontograma-formulario">
      <div class="campo-sm">
        <label for="numeroDiente">Diente:</label>
        <input type="text" id="numeroDiente" readonly>
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
window.pacienteEdad = <?php echo $edadPaciente !== null ? (int)$edadPaciente : 'null'; ?>;
window.tipoDenticionInicial = <?php echo json_encode($tipoDenticion); ?>;
</script>
<script src="js/app_odontograma_bd.js"></script>

<?php include '_layout_bottom.php'; ?>