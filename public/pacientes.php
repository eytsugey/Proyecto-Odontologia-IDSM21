<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor','secretaria']);
$titulo='Pacientes'; $subtitulo='Alta y consulta de pacientes'; $active='pacientes';
include '_layout_top.php';
$buscar = trim($_GET['buscar'] ?? '');
if ($buscar !== '') {
  $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE nombre LIKE ? OR telefono LIKE ? ORDER BY id DESC');
  $like = "%$buscar%";
  $stmt->execute([$like, $like]);
  $pacientes = $stmt->fetchAll();
} else {
  $pacientes = $pdo->query('SELECT * FROM pacientes ORDER BY id DESC')->fetchAll();
}
?>
<section class="panel">
  <h2>Registrar paciente</h2>
  <?php if (isset($_GET['ok'])): ?><div class="notice success">Paciente registrado correctamente.</div><?php endif; ?>
  <form class="form-grid" action="../api/pacientes.php" method="POST">
    <div class="field field-wide"><label>Nombre</label><input type="text" name="nombre" required></div>
    <div class="field"><label>Sexo</label><select name="sexo"><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option></select></div>
    <div class="field"><label>Edad</label><input type="number" name="edad"></div>
    <div class="field"><label>Fecha de nacimiento</label><input type="date" name="fecha_nacimiento"></div>
    <div class="field"><label>Teléfono celular</label><input type="text" name="telefono" required></div>
    <div class="field field-wide"><button class="btn" type="submit">Guardar paciente</button></div>
  </form>
</section>
<section class="panel">
  <div class="toolbar">
    <h2>Lista de pacientes</h2>
    <form method="GET" style="display:flex; gap:8px;">
      <input type="text" name="buscar" placeholder="Buscar por nombre o teléfono" value="<?php echo htmlspecialchars($buscar); ?>">
      <button class="btn-secondary" type="submit">Buscar</button>
    </form>
  </div>
  <table class="table">
    <thead><tr><th>ID</th><th>Nombre</th><th>Sexo</th><th>Edad</th><th>Fecha nacimiento</th><th>Teléfono</th></tr></thead>
    <tbody><?php foreach ($pacientes as $p): ?><tr><td><?php echo $p['id']; ?></td><td><?php echo htmlspecialchars($p['nombre']); ?></td><td><?php echo htmlspecialchars($p['sexo']); ?></td><td><?php echo htmlspecialchars((string)$p['edad']); ?></td><td><?php echo htmlspecialchars((string)$p['fecha_nacimiento']); ?></td><td><?php echo htmlspecialchars($p['telefono']); ?></td></tr><?php endforeach; ?></tbody>
  </table>
</section>
<?php include '_layout_bottom.php'; ?>
