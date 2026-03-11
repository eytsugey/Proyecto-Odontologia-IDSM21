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

$stmt = $pdo->prepare('SELECT * FROM historias_clinicas WHERE paciente_id = ? LIMIT 1');
$stmt->execute([$pacienteId]);
$historia = $stmt->fetch();

$titulo = 'Historia clínica';
$subtitulo = 'Registro clínico de ' . $paciente['nombre'];
$active = 'pacientes';

$ok = isset($_GET['ok']);

include '_layout_top.php';
?>

<?php if ($ok): ?>
<div class="notice success">Historia clínica guardada.</div>
<?php endif; ?>

<section class="panel">
  <div class="toolbar">
    <h2><?php echo htmlspecialchars($paciente['nombre']); ?></h2>
    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <form action="../api/historia.php" method="POST">
    <input type="hidden" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">

    <div class="form-grid">
      <div class="field field-wide">
        <label>Alergias</label>
        <input type="text" name="alergias" value="<?php echo htmlspecialchars($historia['alergias'] ?? ''); ?>">
      </div>

      <div class="field field-wide">
        <label>Cirugías</label>
        <input type="text" name="cirugias" value="<?php echo htmlspecialchars($historia['cirugias'] ?? ''); ?>">
      </div>

      <div class="field">
        <label>Diabetes</label>
        <input type="text" name="diabetes" value="<?php echo htmlspecialchars($historia['diabetes'] ?? ''); ?>">
      </div>

      <div class="field">
        <label>Presión alta</label>
        <input type="text" name="presion_alta" value="<?php echo htmlspecialchars($historia['presion_alta'] ?? ''); ?>">
      </div>

      <div class="field">
        <label>Presión baja</label>
        <input type="text" name="presion_baja" value="<?php echo htmlspecialchars($historia['presion_baja'] ?? ''); ?>">
      </div>

      <div class="field">
        <label>Medicamentos</label>
        <input type="text" name="medicamentos" value="<?php echo htmlspecialchars($historia['medicamentos'] ?? ''); ?>">
      </div>

      <div class="field field-wide">
        <label>Hábitos</label>
        <input type="text" name="habitos" value="<?php echo htmlspecialchars($historia['habitos'] ?? ''); ?>">
      </div>

      <div class="field field-wide">
        <label>Motivo de consulta</label>
        <textarea name="motivo_consulta" rows="4"><?php echo htmlspecialchars($historia['motivo_consulta'] ?? ''); ?></textarea>
      </div>

      <div class="field field-wide">
        <button class="btn" type="submit">Guardar historia clínica</button>
      </div>
    </div>
  </form>
</section>

<?php include '_layout_bottom.php'; ?>