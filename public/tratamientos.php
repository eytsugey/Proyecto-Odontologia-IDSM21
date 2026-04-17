<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'secretaria', 'doctor']);
ensureProfessionalModules($pdo);

$rolActual = normalizarRol($_SESSION['rol'] ?? '');

$titulo = 'Tratamientos';
$subtitulo = 'Catálogo clínico con precios base para cotización y cobro';
$active = 'tratamientos';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$tratamientos = $pdo->query('SELECT * FROM tratamientos_catalogo ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="cards compact-cards">
  <div class="card"><h3>Tratamientos activos</h3><p><?php echo count(array_filter($tratamientos, fn($t) => (int)$t['activo'] === 1)); ?></p></div>
  <div class="card"><h3>Precio promedio</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($tratamientos ? array_sum(array_map(fn($t)=>(float)$t['precio_base'], $tratamientos)) / count($tratamientos) : 0)); ?></p></div>
  <div class="card"><h3>Catálogo total</h3><p><?php echo count($tratamientos); ?> servicios configurados.</p></div>
  <div class="card"><h3>Permiso actual</h3><p><?php echo $rolActual === 'doctor' ? 'Edición habilitada para doctor.' : 'Gestión completa del catálogo.'; ?></p></div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Nuevo tratamiento</h2>
      <p>Define el nombre y el precio base para usarlo después en citas y pagos.</p>
    </div>
  </div>

  <?php if (isset($_GET['ok'])): ?>
    <div class="notice success">Cambios guardados correctamente.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="notice error">Revisa los datos del tratamiento.</div>
  <?php endif; ?>

    <form class="form-grid compact-gap" action="<?php echo htmlspecialchars(appApiUrl('tratamientos.php')); ?>" method="POST">
      <div class="field span-5">
        <label>Nombre</label>
        <input type="text" name="nombre" placeholder="Limpieza dental" required>
      </div>
      <div class="field span-2">
        <label>Precio base</label>
        <input type="number" name="precio_base" min="0.01" step="0.01" placeholder="450.00" required>
      </div>
      <div class="field span-12">
        <label>Descripción</label>
        <textarea name="descripcion" rows="3" placeholder="Notas del procedimiento, duración o materiales"></textarea>
      </div>
      <div class="field span-12 form-actions-right">
        <button class="btn" type="submit">Guardar tratamiento</button>
      </div>
    </form>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Catálogo actual</h2>
      <p>Puedes ajustar precio, nombre, descripción y activar o desactivar tratamientos desde este catálogo.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tratamiento</th>
          <th>Precio base</th>
          <th>Descripción</th>
          <th>Estado</th>
          <th>Editar</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$tratamientos): ?>
          <tr><td colspan="6">Todavía no hay tratamientos configurados.</td></tr>
        <?php else: ?>
          <?php foreach ($tratamientos as $t): ?>
            <tr>
              <td><?php echo htmlspecialchars($t['nombre']); ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$t['precio_base'])); ?></td>
              <td><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></td>
              <td><span class="badge <?php echo (int)$t['activo'] === 1 ? 'confirmada' : 'cancelada'; ?>"><?php echo (int)$t['activo'] === 1 ? 'Activo' : 'Inactivo'; ?></span></td>
              <td>
                <form class="inline-form" action="<?php echo htmlspecialchars(appApiUrl('tratamientos.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="tratamiento_id" value="<?php echo (int)$t['id']; ?>">
                  <input type="text" name="nombre" value="<?php echo htmlspecialchars($t['nombre']); ?>" required>
                  <input type="number" name="precio_base" min="0.01" step="0.01" value="<?php echo htmlspecialchars((string)$t['precio_base']); ?>" required>
                  <input type="text" name="descripcion" value="<?php echo htmlspecialchars($t['descripcion'] ?? ''); ?>" placeholder="Descripción">
                  <button class="btn-small" type="submit">Guardar</button>
                </form>
              </td>
              <td>
                <form action="<?php echo htmlspecialchars(appApiUrl('tratamientos.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="tratamiento_id" value="<?php echo (int)$t['id']; ?>">
                  <button class="btn-small secondary" type="submit"><?php echo (int)$t['activo'] === 1 ? 'Desactivar' : 'Activar'; ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
