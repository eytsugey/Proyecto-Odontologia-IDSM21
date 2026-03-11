<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

$titulo = 'Pacientes';
$subtitulo = 'Desde esta vista puedes registrar pacientes y abrir su historia clínica, odontograma y citas.';
$active = 'pacientes';

$buscar = trim($_GET['buscar'] ?? '');

if ($buscar !== '') {
    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE nombre LIKE ? OR telefono LIKE ? ORDER BY id DESC');
    $term = "%$buscar%";
    $stmt->execute([$term, $term]);
    $pacientes = $stmt->fetchAll();
} else {
    $pacientes = $pdo->query('SELECT * FROM pacientes ORDER BY id DESC')->fetchAll();
}

$ok = isset($_GET['ok']);
$error = isset($_GET['error']);

include '_layout_top.php';
?>

<?php if ($ok): ?>
<div class="notice success">Paciente registrado correctamente.</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="notice error">Nombre, sexo y teléfono son obligatorios.</div>
<?php endif; ?>

<section class="panel">
  <div class="toolbar">
    <h2>Registrar paciente</h2>
  </div>

  <form action="../api/pacientes.php" method="POST">
    <div class="form-grid">
      <div class="field field-wide">
        <label>Nombre</label>
        <input type="text" name="nombre" required>
      </div>

      <div class="field">
        <label>Sexo</label>
        <select name="sexo" required>
          <option value="">Selecciona</option>
          <option value="M">Masculino</option>
          <option value="F">Femenino</option>
          <option value="Otro">Otro</option>
        </select>
      </div>

      <div class="field">
        <label>Edad</label>
        <input type="number" name="edad" min="0" max="120">
      </div>

      <div class="field">
        <label>Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento">
      </div>

      <div class="field">
        <label>Teléfono</label>
        <input type="text" name="telefono" required>
      </div>

      <div class="field field-wide">
        <button class="btn" type="submit">Guardar paciente</button>
      </div>
    </div>
  </form>
</section>

<section class="panel">
  <div class="toolbar">
    <h2>Lista de pacientes</h2>
    <form method="GET" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <input type="text" name="buscar" placeholder="Buscar por nombre o teléfono" value="<?php echo htmlspecialchars($buscar); ?>">
      <button type="submit" class="btn-secondary">Buscar</button>
      <a href="pacientes.php" class="btn-secondary">Limpiar</a>
    </form>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Sexo</th>
        <th>Edad</th>
        <th>Fecha nacimiento</th>
        <th>Teléfono</th>
        <th>Acciones clínicas</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$pacientes): ?>
        <tr><td colspan="6">No hay pacientes registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($pacientes as $p): ?>
          <tr>
            <td><?php echo htmlspecialchars($p['nombre']); ?></td>
            <td><?php echo htmlspecialchars($p['sexo'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($p['edad'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($p['fecha_nacimiento'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($p['telefono']); ?></td>
            <td style="display:flex; gap:8px; flex-wrap:wrap;">
              <a class="btn-secondary" href="historia.php?paciente_id=<?php echo (int)$p['id']; ?>">Historia clínica</a>
              <a class="btn-secondary" href="odontograma.php?paciente_id=<?php echo (int)$p['id']; ?>">Odontograma</a>
              <a class="btn-secondary" href="citas_paciente.php?paciente_id=<?php echo (int)$p['id']; ?>">Ver citas</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php include '_layout_bottom.php'; ?>