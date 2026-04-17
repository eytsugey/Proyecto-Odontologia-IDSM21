<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/helpers/schema.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
require_once __DIR__ . '/../api/middleware/auth.php';

requireLogin(['doctor', 'secretaria']);
ensureProfessionalModules($pdo);

if (!($pdo instanceof PDO)) {
    die('Error de conexión a la base de datos.');
}

$titulo = 'Citas';
$subtitulo = 'Registrar citas y controlar la atención sin saturar la agenda visualmente';
$active = 'citas';
$extra_css = ['css/modulos.css', 'css/citas.css'];

$pacientes = [];
$doctores = [];
$tratamientos = [];
$citas = [];
$cuentasPorCita = [];

try {
    $pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pacientes = [];
}

try {
    $doctores = obtenerDoctores($pdo);
} catch (Throwable $e) {
    $doctores = [];
}

try {
    $tratamientos = pmTratamientosActivos($pdo);
} catch (Throwable $e) {
    $tratamientos = [];
}

try {
    $citas = $pdo->query(obtenerSQLListadoCitas($pdo))->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $citas = [];
}

foreach ($citas as $cita) {
    $cuentasPorCita[(int) $cita['id']] = pmResumenCuentaCita($pdo, (int) $cita['id']);
}

$citasHoy = count(array_filter($citas, static fn(array $c): bool => ($c['fecha'] ?? '') === date('Y-m-d')));
$conTratamiento = count(array_filter($citas, static fn(array $c): bool => trim((string) ($c['motivo_consulta'] ?? '')) !== ''));
$pendienteTotal = array_sum(array_map(static fn(array $r): float => (float) ($r['saldo'] ?? 0), $cuentasPorCita));

include '_layout_top.php';
?>

<section class="cards compact-cards">
  <div class="card"><h3>Citas del día</h3><p><?php echo $citasHoy; ?></p></div>
  <div class="card"><h3>Con motivo definido</h3><p><?php echo $conTratamiento; ?></p></div>
  <div class="card"><h3>Pendiente por cobrar</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($pendienteTotal)); ?></p></div>
</section>

<section class="panel">
  <h2>Nueva cita</h2>

  <?php if (isset($_GET['ok']) && $_GET['ok'] === '1'): ?>
    <div class="notice success">Cita registrada correctamente.</div>
  <?php endif; ?>

  <?php if (isset($_GET['ok']) && $_GET['ok'] === 'estado'): ?>
    <div class="notice success">El estado de la cita se actualizó correctamente.</div>
  <?php endif; ?>

  <?php if (isset($_GET['choque'])): ?>
    <div class="notice error">Ya existe una cita del doctor a esa hora.</div>
  <?php endif; ?>

  <?php if (isset($_GET['error']) && $_GET['error'] === 'doctor'): ?>
    <div class="notice error">No se encontró un doctor válido para registrar la cita.</div>
  <?php endif; ?>

  <?php if (empty($pacientes)): ?>
    <div class="notice error">Primero debes registrar al menos un paciente.</div>
  <?php endif; ?>

  <?php if (empty($doctores)): ?>
    <div class="notice error">No hay doctores disponibles para asignar citas.</div>
  <?php endif; ?>

  <form class="form-citas" action="<?php echo htmlspecialchars(appApiUrl('citas.php')); ?>" method="POST">
    <div class="field span-6">
      <label for="paciente_id">Paciente</label>
      <select id="paciente_id" name="paciente_id" required <?php echo empty($pacientes) ? 'disabled' : ''; ?>>
        <option value="">Selecciona un paciente</option>
        <?php foreach ($pacientes as $p): ?>
          <option value="<?php echo (int) $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field span-6">
      <label for="doctor_valor">Doctor</label>
      <select id="doctor_valor" name="doctor_valor" required <?php echo empty($doctores) ? 'disabled' : ''; ?>>
        <option value="">Selecciona un doctor</option>
        <?php foreach ($doctores as $d): ?>
          <option value="<?php echo htmlspecialchars($d['valor']); ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field span-3">
      <label for="fecha">Fecha</label>
      <input type="date" id="fecha" name="fecha" min="<?php echo date('Y-m-d'); ?>" required>
    </div>

    <div class="field span-3">
      <label for="hora">Hora</label>
      <input type="time" id="hora" name="hora" required>
    </div>

    <div class="field span-3">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <option value="pendiente">Pendiente</option>
        <option value="confirmada">Confirmada</option>
      </select>
    </div>

    <div class="field span-9">
      <label for="motivo_consulta">Motivo de consulta</label>
      <input
        type="text"
        id="motivo_consulta"
        name="motivo_consulta"
        list="lista_tratamientos"
        placeholder="Escribe el tratamiento o motivo principal"
        required
      >
      <small class="muted">Usa el nombre del tratamiento principal. Si coincide con el catálogo, se ligará automáticamente.</small>

      <datalist id="lista_tratamientos">
        <?php foreach ($tratamientos as $t): ?>
          <option value="<?php echo htmlspecialchars($t['nombre']); ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="field span-12 form-actions-right">
      <button class="btn" type="submit" <?php echo (empty($pacientes) || empty($doctores)) ? 'disabled' : ''; ?>>Guardar cita</button>
    </div>
  </form>
</section>

<section class="panel panel-agenda">
  <h2>Agenda clínica y cuenta</h2>

  <div class="table-wrap citas-wrap">
    <table class="table table-citas">
      <thead>
        <tr>
          <th>Paciente</th>
          <th>Doctor</th>
          <th>Horario</th>
          <th>Motivo</th>
          <th>Cuenta</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($citas)): ?>
          <tr>
            <td colspan="7" class="cita-vacia">No hay citas registradas por el momento.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($citas as $c): ?>
            <?php
              $citaId = (int) ($c['id'] ?? 0);
              $estadoActual = strtolower(trim((string) ($c['estado'] ?? 'pendiente')));
              $cuenta = $cuentasPorCita[$citaId] ?? ['total' => 0, 'abonado' => 0, 'saldo' => 0];
              $total = (float) ($cuenta['total'] ?? 0);
              $abonado = (float) ($cuenta['abonado'] ?? 0);
              $saldo = (float) ($cuenta['saldo'] ?? 0);
            ?>
            <tr>
              <td data-label="Paciente"><?php echo htmlspecialchars($c['paciente'] ?? ''); ?></td>

              <td data-label="Doctor"><?php echo htmlspecialchars($c['doctor'] ?? ''); ?></td>

              <td data-label="Horario">
                <div class="cita-horario">
                  <span class="fecha"><?php echo htmlspecialchars($c['fecha'] ?? ''); ?></span>
                  <span class="hora"><?php echo htmlspecialchars(substr((string) ($c['hora'] ?? ''), 0, 5)); ?></span>
                </div>
              </td>

              <td data-label="Motivo">
                <div class="cita-motivo">
                  <?php echo htmlspecialchars($c['motivo_consulta'] ?? 'Sin motivo'); ?>
                </div>
              </td>

              <td data-label="Cuenta">
                <div class="cita-cuenta">
                  <span>Total: <?php echo htmlspecialchars(pmFormatoMoneda($total)); ?></span>
                  <span>Abonado: <?php echo htmlspecialchars(pmFormatoMoneda($abonado)); ?></span>
                  <strong>Saldo: <?php echo htmlspecialchars(pmFormatoMoneda($saldo)); ?></strong>

                  <?php if ($saldo > 0): ?>
                    <a class="btn btn-cobrar-inline" href="pagos.php?cita_id=<?php echo $citaId; ?>">Cobrar</a>
                  <?php endif; ?>
                </div>
              </td>

              <td data-label="Estado">
                <div class="estado-wrap">
                  <span class="badge <?php echo htmlspecialchars($estadoActual); ?>"><?php echo htmlspecialchars(ucfirst($estadoActual)); ?></span>
                </div>
              </td>

              <td data-label="Acciones">
                <div class="cita-acciones">
                  <a class="action-link" href="citas_paciente.php?cita_id=<?php echo $citaId; ?>">Editar</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
