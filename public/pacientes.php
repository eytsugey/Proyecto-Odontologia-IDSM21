<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

$titulo = 'Pacientes';
$subtitulo = 'Desde esta vista puedes registrar pacientes y abrir su historia clínica, odontograma y citas.';
$active = 'pacientes';

$buscar = trim($_GET['buscar'] ?? '');
$letra = trim($_GET['letra'] ?? '');

$sql = 'SELECT * FROM pacientes WHERE 1=1';
$params = [];

if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE ? OR telefono LIKE ?)';
    $term = "%$buscar%";
    $params[] = $term;
    $params[] = $term;
}

if ($letra !== '') {
    $sql .= ' AND nombre LIKE ?';
    $params[] = $letra . '%';
}

$sql .= ' ORDER BY nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pacientes = $stmt->fetchAll();

$ok = isset($_GET['ok']);
$error = isset($_GET['error']);

include '_layout_top.php';
?>

<div class="page-pacientes">

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

  <div class="dental-tip">
    <div class="icon">🦷</div>
    <p>Registra los datos básicos del paciente para acceder rápidamente a su historia clínica, odontograma y control de citas.</p>
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
  </div>

  <div class="search-tools">
    <form method="GET" class="search-form">
      <input 
        type="text" 
        name="buscar" 
        placeholder="Buscar por nombre o teléfono" 
        value="<?php echo htmlspecialchars($buscar); ?>"
      >
      <button type="submit" class="btn">Buscar</button>
      <a href="pacientes.php" class="btn-secondary">Limpiar</a>
    </form>

    <div class="letter-filter">
      <?php foreach (range('A', 'Z') as $ltr): ?>
        <a 
          class="btn-letter <?php echo ($letra === $ltr) ? 'active' : ''; ?>" 
          href="pacientes.php?letra=<?php echo $ltr; ?>"
        >
          <?php echo $ltr; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="table-wrap">
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
          <tr class="empty-row">
            <td colspan="6">No hay pacientes registrados.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($pacientes as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['nombre']); ?></td>
              <td>
                <?php
                  $sexo = $p['sexo'] ?? '';
                  $clase = 'badge-o';
                  if ($sexo === 'M') $clase = 'badge-m';
                  elseif ($sexo === 'F') $clase = 'badge-f';
                ?>
                <span class="badge-sex <?php echo $clase; ?>">
                  <?php echo htmlspecialchars($sexo); ?>
                </span>
              </td>
              <td><?php echo htmlspecialchars($p['edad'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($p['fecha_nacimiento'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($p['telefono']); ?></td>
              <td>
                <div class="actions">
                  <a class="btn-secondary" href="historia.php?paciente_id=<?php echo (int)$p['id']; ?>">Historia clínica</a>
                  <a class="btn-secondary" href="odontograma.php?paciente_id=<?php echo (int)$p['id']; ?>">Odontograma</a>
                  <a class="btn-secondary" href="citas_paciente.php?paciente_id=<?php echo (int)$p['id']; ?>">Ver citas</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

</div>

<?php include '_layout_bottom.php'; ?>