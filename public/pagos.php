<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'secretaria']);
ensureProfessionalModules($pdo);

$titulo = 'Pagos';
$subtitulo = 'Cobros vinculados a cita, tratamientos y saldo pendiente';
$active = 'pagos';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre ASC')->fetchAll();
$tratamientos = pmTratamientosActivos($pdo);
$citasCobro = pmCitasCobroPendiente($pdo);
$selectedCitaId = (int)($_GET['cita_id'] ?? 0);
$selectedCita = null;
foreach ($citasCobro as $cobro) {
    if ((int)$cobro['id'] === $selectedCitaId) {
        $selectedCita = $cobro;
        break;
    }
}

$pagos = $pdo->query("SELECT pg.*, p.nombre AS paciente, u.nombre AS usuario
    FROM pagos pg
    LEFT JOIN pacientes p ON p.id = pg.paciente_id
    LEFT JOIN usuarios u ON u.id = pg.registrado_por
    ORDER BY pg.fecha_pago DESC, pg.id DESC
    LIMIT 50")->fetchAll();

$totalHoy = (float)$pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago = CURDATE() AND estado IN ('pagado','parcial')")->fetchColumn();
$totalPendiente = array_sum(array_map(fn($c) => (float)$c['saldo'], $citasCobro));
$totalMes = (float)$pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE YEAR(fecha_pago)=YEAR(CURDATE()) AND MONTH(fecha_pago)=MONTH(CURDATE()) AND estado IN ('pagado','parcial')")->fetchColumn();
?>

<section class="cards compact-cards">
  <div class="card"><h3>Ingreso de hoy</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($totalHoy)); ?></p></div>
  <div class="card"><h3>Saldo clínico pendiente</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$totalPendiente)); ?></p></div>
  <div class="card"><h3>Ingreso mensual</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($totalMes)); ?></p></div>
</section>

<section class="panel two-col-panel wide-right">
  <div>
    <div class="section-head">
      <div>
        <h2>Registrar pago</h2>
        <p>Si eliges una cita, el pago se liga al paciente y al tratamiento.</p>
      </div>
    </div>

    <?php if (isset($_GET['ok'])): ?>
      <div class="notice success">Pago registrado correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="notice error">Revisa los datos del pago o la cita seleccionada.</div>
    <?php endif; ?>

    <form class="form-grid compact-gap" action="<?php echo htmlspecialchars(appApiUrl('pagos.php')); ?>" method="POST">
      <div class="field span-12">
        <label>Cita relacionada</label>
        <select name="cita_id" id="cita_id">
          <option value="0">Sin ligar a cita</option>
          <?php foreach ($citasCobro as $c): ?>
            <option value="<?php echo (int)$c['id']; ?>" <?php echo (int)$c['id'] === $selectedCitaId ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars('#' . $c['id'] . ' · ' . $c['paciente'] . ' · ' . $c['fecha'] . ' ' . substr($c['hora'], 0, 5) . ' · saldo ' . pmFormatoMoneda((float)$c['saldo'])); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-6">
        <label>Paciente</label>
        <select name="paciente_id">
          <option value="0">General / mostrador</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>" <?php echo $selectedCita && (int)$p['id'] === (int)$selectedCita['paciente_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-6">
        <label>Tratamiento</label>
        <select name="tratamiento_id">
          <option value="0">Sin especificar</option>
          <?php foreach ($tratamientos as $t): ?>
            <option value="<?php echo (int)$t['id']; ?>"><?php echo htmlspecialchars($t['nombre'] . ' - ' . pmFormatoMoneda((float)$t['precio_base'])); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-8">
        <label>Concepto</label>
        <input type="text" name="concepto" value="<?php echo htmlspecialchars($selectedCita ? 'Pago cita #' . $selectedCitaId : ''); ?>" placeholder="Pago de consulta, abono, tratamiento" required>
      </div>
      <div class="field span-4">
        <label>Monto</label>
        <input type="number" name="monto" min="0.01" step="0.01" value="<?php echo htmlspecialchars($selectedCita ? number_format((float)$selectedCita['saldo'], 2, '.', '') : ''); ?>" required>
      </div>
      <div class="field span-3">
        <label>Fecha de pago</label>
        <input type="date" name="fecha_pago" value="<?php echo date('Y-m-d'); ?>" required>
      </div>
      <div class="field span-3">
        <label>Método</label>
        <select name="metodo_pago">
          <option value="efectivo">Efectivo</option>
          <option value="tarjeta">Tarjeta</option>
          <option value="transferencia">Transferencia</option>
          <option value="otro">Otro</option>
        </select>
      </div>
      <div class="field span-3">
        <label>Estado</label>
        <select name="estado">
          <option value="pagado">Pagado</option>
          <option value="parcial">Parcial</option>
          <option value="pendiente">Pendiente</option>
        </select>
      </div>
      <div class="field span-3">
        <label>Referencia</label>
        <input type="text" name="referencia" placeholder="Folio, transferencia, nota">
      </div>
      <div class="field span-12">
        <label>Observaciones</label>
        <textarea name="observaciones" rows="3" placeholder="Detalle del pago, abono o autorización"></textarea>
      </div>
      <div class="field span-12 form-actions-right">
        <button class="btn" type="submit">Guardar pago</button>
      </div>
    </form>
  </div>

  <div>
    <div class="section-head">
      <div>
        <h2>Cuentas por cita</h2>
        <p>Consulta tratamientos, abonado y saldo restante.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Cita</th>
            <th>Tratamientos</th>
            <th>Total</th>
            <th>Abonado</th>
            <th>Saldo</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$citasCobro): ?>
            <tr><td colspan="6">Aún no hay citas con tratamientos asignados.</td></tr>
          <?php else: ?>
            <?php foreach ($citasCobro as $c): ?>
              <tr>
                <td><a href="pagos.php?cita_id=<?php echo (int)$c['id']; ?>"><?php echo htmlspecialchars('#' . $c['id'] . ' · ' . $c['paciente']); ?></a></td>
                <td><?php echo htmlspecialchars($c['tratamientos'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$c['total_tratamientos'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$c['abonado'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$c['saldo'])); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($c['estado_cuenta']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $c['estado_cuenta']))); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Movimientos recientes</h2>
      <p>Últimos 50 registros capturados en el sistema.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Paciente</th>
          <th>Cita</th>
          <th>Concepto</th>
          <th>Monto</th>
          <th>Método</th>
          <th>Estado</th>
          <th>Capturó</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$pagos): ?>
          <tr><td colspan="8">Aún no hay pagos registrados.</td></tr>
        <?php else: ?>
          <?php foreach ($pagos as $pg): ?>
            <tr>
              <td><?php echo htmlspecialchars($pg['fecha_pago']); ?></td>
              <td><?php echo htmlspecialchars($pg['paciente'] ?: 'General'); ?></td>
              <td><?php echo $pg['cita_id'] ? '#' . (int)$pg['cita_id'] : '—'; ?></td>
              <td><?php echo htmlspecialchars($pg['concepto']); ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$pg['monto'])); ?></td>
              <td><?php echo htmlspecialchars(ucfirst($pg['metodo_pago'])); ?></td>
              <td><span class="badge <?php echo htmlspecialchars($pg['estado']); ?>"><?php echo htmlspecialchars(ucfirst($pg['estado'])); ?></span></td>
              <td><?php echo htmlspecialchars($pg['usuario'] ?: 'Sistema'); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>
