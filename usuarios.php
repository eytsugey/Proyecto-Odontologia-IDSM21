<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin']);
ensureProfessionalModules($pdo);

$titulo = 'Usuarios';
$subtitulo = 'Control de accesos, roles y contraseñas del sistema';
$active = 'usuarios';
$extra_css = ['css/modulos.css', 'css/usuarios.css'];
include '_layout_top.php';

$usuarios = $pdo->query('SELECT id, nombre, correo, rol, COALESCE(activo,1) AS activo, creado_en FROM usuarios ORDER BY nombre ASC')->fetchAll();
?>

<section class="cards compact-cards">
  <div class="card"><h3>Total usuarios</h3><p><?php echo count($usuarios); ?> cuentas registradas.</p></div>
  <div class="card"><h3>Administradores</h3><p><?php echo count(array_filter($usuarios, fn($u) => normalizarRol($u['rol']) === 'admin')); ?> con control total.</p></div>
  <div class="card"><h3>Activos</h3><p><?php echo count(array_filter($usuarios, fn($u) => (int)$u['activo'] === 1)); ?> con acceso habilitado.</p></div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Nuevo usuario</h2>
      <p>Crea cuentas para doctor, secretaria o administrador.</p>
    </div>
  </div>

  <?php if (isset($_GET['ok'])): ?>
    <div class="notice success">Movimiento aplicado correctamente.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="notice error">No se pudo completar la acción. Revisa datos, correo o contraseña.</div>
  <?php endif; ?>

  <form class="form-grid compact-gap" action="<?php echo htmlspecialchars(appApiUrl('usuarios.php')); ?>" method="POST">
    <div class="field span-4">
      <label>Nombre completo</label>
      <input type="text" name="nombre" required>
    </div>
    <div class="field span-4">
      <label>Correo</label>
      <input type="email" name="correo" required>
    </div>
    <div class="field span-2">
      <label>Rol</label>
      <select name="rol">
        <option value="secretaria">Secretaria</option>
        <option value="doctor">Doctor</option>
        <option value="admin">Administrador</option>
      </select>
    </div>
    <div class="field span-2">
      <label>Contraseña temporal</label>
      <input type="text" name="password" minlength="4" required>
    </div>
    <div class="field span-12 form-actions-right">
      <button class="btn" type="submit">Crear usuario</button>
    </div>
  </form>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Accesos registrados</h2>
      <p>Administra rol, estado y restablecimiento de contraseña.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="table usuarios-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Correo</th>
          <th>Rol</th>
          <th>Estado</th>
          <th>Cambiar rol</th>
          <th>Contraseña</th>
          <th>Acceso</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?php echo htmlspecialchars($u['nombre']); ?></td>
            <td><?php echo htmlspecialchars($u['correo']); ?></td>
            <td><span class="badge neutral"><?php echo htmlspecialchars(ucfirst(normalizarRol($u['rol']))); ?></span></td>
            <td>
              <span class="badge <?php echo (int)$u['activo'] === 1 ? 'confirmada' : 'cancelada'; ?>">
                <?php echo (int)$u['activo'] === 1 ? 'Activo' : 'Inactivo'; ?>
              </span>
            </td>
            <td>
              <form class="inline-form control-grid compact" action="<?php echo htmlspecialchars(appApiUrl('usuarios.php')); ?>" method="POST">
                <input type="hidden" name="action" value="role">
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <select name="rol">
                  <option value="secretaria" <?php echo normalizarRol($u['rol']) === 'secretaria' ? 'selected' : ''; ?>>Secretaria</option>
                  <option value="doctor" <?php echo normalizarRol($u['rol']) === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                  <option value="admin" <?php echo normalizarRol($u['rol']) === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                </select>
                <button class="btn-small" type="submit">Guardar</button>
              </form>
            </td>
            <td>
              <form class="inline-form control-grid compact" action="<?php echo htmlspecialchars(appApiUrl('usuarios.php')); ?>" method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <input type="text" name="nuevo_password" placeholder="Nueva clave" minlength="4" required>
                <button class="btn-small" type="submit">Restablecer</button>
              </form>
            </td>
            <td>
              <form action="<?php echo htmlspecialchars(appApiUrl('usuarios.php')); ?>" method="POST">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                <button class="btn-small secondary" type="submit">
                  <?php echo (int)$u['activo'] === 1 ? 'Desactivar' : 'Activar'; ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
