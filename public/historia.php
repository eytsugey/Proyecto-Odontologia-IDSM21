<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor','secretaria']);
$titulo='Historia clínica'; $subtitulo='La secretaria registra el resto del historial'; $active='historia';
include '_layout_top.php';
$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre')->fetchAll();
$pacienteId = (int)($_GET['paciente_id'] ?? ($pacientes[0]['id'] ?? 0));
$stmt = $pdo->prepare('SELECT * FROM historias_clinicas WHERE paciente_id = ? LIMIT 1');
$stmt->execute([$pacienteId]);
$h = $stmt->fetch() ?: ['alergias'=>'','cirugias'=>'','diabetes'=>'','presion_alta'=>'','presion_baja'=>'','medicamentos'=>'','habitos'=>'','motivo_consulta'=>''];
?>
<section class="panel">
  <h2>Captura de historia clínica</h2>
  <?php if (isset($_GET['ok'])): ?><div class="notice success">Historia clínica guardada.</div><?php endif; ?>
  <form class="form-grid" action="../api/historia.php" method="POST">
    <div class="field field-wide"><label>Paciente</label><select name="paciente_id" onchange="window.location='historia.php?paciente_id='+this.value"><?php foreach($pacientes as $p): ?><option value="<?php echo $p['id']; ?>" <?php echo $pacienteId===$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option><?php endforeach; ?></select></div>
    <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
    <div class="field field-wide"><label>Alergias</label><input type="text" name="alergias" value="<?php echo htmlspecialchars($h['alergias']); ?>"></div>
    <div class="field field-wide"><label>Cirugías</label><input type="text" name="cirugias" value="<?php echo htmlspecialchars($h['cirugias']); ?>"></div>
    <div class="field"><label>Diabetes</label><input type="text" name="diabetes" value="<?php echo htmlspecialchars($h['diabetes']); ?>"></div>
    <div class="field"><label>Presión alta</label><input type="text" name="presion_alta" value="<?php echo htmlspecialchars($h['presion_alta']); ?>"></div>
    <div class="field"><label>Presión baja</label><input type="text" name="presion_baja" value="<?php echo htmlspecialchars($h['presion_baja']); ?>"></div>
    <div class="field field-wide"><label>Medicamentos</label><textarea name="medicamentos"><?php echo htmlspecialchars($h['medicamentos']); ?></textarea></div>
    <div class="field field-wide"><label>Hábitos</label><textarea name="habitos"><?php echo htmlspecialchars($h['habitos']); ?></textarea></div>
    <div class="field field-wide"><label>Motivo de consulta</label><textarea name="motivo_consulta"><?php echo htmlspecialchars($h['motivo_consulta']); ?></textarea></div>
    <div class="field field-wide"><button class="btn" type="submit">Guardar historia clínica</button></div>
  </form>
</section>
<?php include '_layout_bottom.php'; ?>
