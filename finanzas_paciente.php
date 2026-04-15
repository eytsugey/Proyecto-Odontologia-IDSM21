<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'secretaria', 'doctor']);
ensureProfessionalModules($pdo);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$titulo = 'Estado financiero por paciente';
$subtitulo = 'Consulta cargos reales, abonos, saldo y planes por fases del paciente';
$active = 'finanzas_paciente';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
$resumenes = [];
foreach ($pacientes as $p) {
    $resumenes[] = ['paciente' => $p] + pmResumenFinancieroPaciente($pdo, (int)$p['id']);
}

$pacienteActual = null;
$detalleCitas = [];
$movimientos = [];
$planesFinancieros = [];
$itemsPlaneados = [];
if ($pacienteId > 0) {
    foreach ($pacientes as $p) {
        if ((int)$p['id'] === $pacienteId) {
            $pacienteActual = $p;
            break;
        }
    }

    $stmt = $pdo->prepare("SELECT c.id, c.fecha, c.hora,
            COALESCE(SUM(ct.subtotal),0) AS total,
            (SELECT COALESCE(SUM(pg.monto),0) FROM pagos pg WHERE pg.cita_id = c.id AND pg.estado IN ('pagado','parcial')) AS abonado
        FROM citas c
        LEFT JOIN cita_tratamientos ct ON ct.cita_id = c.id
        WHERE c.paciente_id = ?
        GROUP BY c.id, c.fecha, c.hora
        ORDER BY c.fecha DESC, c.hora DESC");
    $stmt->execute([$pacienteId]);
    $detalleCitas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmt = $pdo->prepare("SELECT fecha_pago, concepto, monto, metodo_pago, estado, cita_id
        FROM pagos WHERE paciente_id = ? ORDER BY fecha_pago DESC, id DESC");
    $stmt->execute([$pacienteId]);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $planesFinancieros = pmDetallePlanesFinancierosPaciente($pdo, $pacienteId);
    $itemsPlaneados = pmDetalleItemsPlaneadosPaciente($pdo, $pacienteId);
}
?>

<section class="panel two-col-panel wide-right">
  <div>
    <div class="section-head">
      <div>
        <h2>Seleccionar paciente</h2>
        <p>Abre el detalle financiero individual.</p>
      </div>
    </div>
    <form class="form-grid compact-form compact-gap" method="GET">
      <div class="field field-wide">
        <label>Paciente</label>
        <select name="paciente_id" required>
          <option value="">Selecciona un paciente</option>
          <?php foreach ($pacientes as $p): ?>
            <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === $pacienteId ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field field-wide">
        <button class="btn" type="submit">Ver estado</button>
      </div>
    </form>
  </div>
  <div>
    <div class="section-head">
      <div>
        <h2>Resumen general</h2>
        <p>Saldo y estimado total de todos los pacientes registrados.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Paciente</th>
            <th>Cargos</th>
            <th>Abonos</th>
            <th>Saldo</th>
            <th>Planes</th>
            <th>Estimado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$resumenes): ?>
            <tr><td colspan="6">No hay pacientes registrados.</td></tr>
          <?php else: ?>
            <?php foreach ($resumenes as $r): ?>
              <tr>
                <td><a href="finanzas_paciente.php?paciente_id=<?php echo (int)$r['paciente']['id']; ?>"><?php echo htmlspecialchars($r['paciente']['nombre']); ?></a></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$r['cargos'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$r['abonos'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$r['saldo'])); ?></td>
                <td><?php echo (int)($r['planes'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)($r['estimado_planes'] ?? 0))); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php if ($pacienteActual): $resumen = pmResumenFinancieroPaciente($pdo, $pacienteId); ?>
<section class="cards compact-cards">
  <div class="card"><h3>Paciente</h3><p><?php echo htmlspecialchars($pacienteActual['nombre']); ?></p></div>
  <div class="card"><h3>Cargos reales</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$resumen['cargos'])); ?></p></div>
  <div class="card"><h3>Abonos</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$resumen['abonos'])); ?></p></div>
  <div class="card"><h3>Saldo real</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$resumen['saldo'])); ?></p></div>
  <div class="card"><h3>Planes por fases</h3><p><?php echo (int)($resumen['planes'] ?? 0); ?></p></div>
  <div class="card"><h3>Estimado en planes</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)($resumen['estimado_planes'] ?? 0))); ?></p></div>
</section>

<section class="panel two-col-panel">
  <div>
    <div class="section-head">
      <div>
        <h2>Cuentas por cita</h2>
        <p>Total, abonado y saldo por cada cita del paciente.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Cita</th>
            <th>Total</th>
            <th>Abonado</th>
            <th>Saldo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$detalleCitas): ?>
            <tr><td colspan="4">No hay citas con cargos registrados.</td></tr>
          <?php else: ?>
            <?php foreach ($detalleCitas as $c): $saldo = max(0, (float)$c['total'] - (float)$c['abonado']); ?>
              <tr>
                <td><?php echo htmlspecialchars('#' . $c['id'] . ' · ' . $c['fecha'] . ' ' . substr((string)$c['hora'],0,5)); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$c['total'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$c['abonado'])); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$saldo)); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div>
    <div class="section-head">
      <div>
        <h2>Movimientos del paciente</h2>
        <p>Pagos, abonos y su método de captura.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Concepto</th>
            <th>Monto</th>
            <th>Método</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$movimientos): ?>
            <tr><td colspan="4">No hay pagos capturados.</td></tr>
          <?php else: ?>
            <?php foreach ($movimientos as $m): ?>
              <tr>
                <td><?php echo htmlspecialchars($m['fecha_pago']); ?></td>
                <td><?php echo htmlspecialchars($m['concepto'] . ($m['cita_id'] ? ' · cita #' . (int)$m['cita_id'] : '')); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$m['monto'])); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($m['metodo_pago'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="panel two-col-panel">
  <div>
    <div class="section-head">
      <div>
        <h2>Planes por fases</h2>
        <p>Los planes ya aparecen dentro del estado financiero como cotización y seguimiento.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Plan</th>
            <th>Estado</th>
            <th>Fases</th>
            <th>Items</th>
            <th>Estimado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$planesFinancieros): ?>
            <tr><td colspan="5">No hay planes registrados para este paciente.</td></tr>
          <?php else: ?>
            <?php foreach ($planesFinancieros as $plan): ?>
              <tr>
                <td>
                  <a href="plan_tratamiento.php?paciente_id=<?php echo $pacienteId; ?>&plan_id=<?php echo (int)$plan['id']; ?>">
                    <?php echo htmlspecialchars($plan['titulo']); ?>
                  </a>
                  <?php if (!empty($plan['cita_id'])): ?>
                    <div class="muted-text">Ligado a cita #<?php echo (int)$plan['cita_id']; ?></div>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?php echo htmlspecialchars($plan['estado']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $plan['estado']))); ?></span></td>
                <td><?php echo (int)$plan['fases']; ?></td>
                <td><?php echo (int)$plan['items']; ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$plan['total_estimado'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div>
    <div class="section-head">
      <div>
        <h2>Items planeados</h2>
        <p>Tratamientos cotizados dentro de los planes por fases.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Plan / fase</th>
            <th>Tratamiento</th>
            <th>Estado</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$itemsPlaneados): ?>
            <tr><td colspan="4">No hay tratamientos planeados registrados.</td></tr>
          <?php else: ?>
            <?php foreach ($itemsPlaneados as $item): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($item['plan']); ?></strong>
                  <div class="muted-text"><?php echo htmlspecialchars($item['fase'] ?: 'Sin fase'); ?></div>
                </td>
                <td>
                  <?php echo htmlspecialchars($item['tratamiento']); ?>
                  <div class="muted-text">Cantidad: <?php echo (int)$item['cantidad']; ?></div>
                </td>
                <td><span class="badge <?php echo htmlspecialchars($item['estado']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['estado']))); ?></span></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['subtotal'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<?php include '_layout_bottom.php'; ?>
