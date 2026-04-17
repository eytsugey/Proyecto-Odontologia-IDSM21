<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'secretaria', 'doctor']);
ensureProfessionalModules($pdo);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$titulo = 'Estado financiero por paciente';
$subtitulo = 'Consulta los tratamientos del paciente y calcula el estimado a partir de sus subtotales';
$active = 'finanzas_paciente';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
$resumenes = [];
foreach ($pacientes as $p) {
    $resumenes[] = ['paciente' => $p] + pmResumenPlanGeneralPaciente($pdo, (int)$p['id']);
}

$pacienteActual = null;
$itemsPlanGeneral = [];
if ($pacienteId > 0) {
    foreach ($pacientes as $p) {
        if ((int)$p['id'] === $pacienteId) {
            $pacienteActual = $p;
            break;
        }
    }

    $itemsPlanGeneral = pmDetalleItemsPlaneadosPacientePorTipo($pdo, $pacienteId, 'general');
}
?>

<section class="panel two-col-panel wide-right">
  <div>
    <div class="section-head">
      <div>
        <h2>Seleccionar paciente</h2>
        <p>Abre el detalle de los tratamientos del plan general.</p>
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
        <p>El estimado se basa en la suma de los subtotales de los tratamientos del paciente.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Paciente</th>
            <th>Tratamientos</th>
            <th>Pagados</th>
            <th>Pendientes</th>
            <th>Total estimado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$resumenes): ?>
            <tr><td colspan="5">No hay pacientes registrados.</td></tr>
          <?php else: ?>
            <?php foreach ($resumenes as $r): ?>
              <tr>
                <td><a href="finanzas_paciente.php?paciente_id=<?php echo (int)$r['paciente']['id']; ?>"><?php echo htmlspecialchars($r['paciente']['nombre']); ?></a></td>
                <td><?php echo (int)($r['tratamientos'] ?? 0); ?></td>
                <td><?php echo (int)($r['pagados'] ?? 0); ?></td>
                <td><?php echo (int)($r['pendientes'] ?? 0); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)($r['estimado'] ?? 0))); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php if ($pacienteActual): $resumen = pmResumenPlanGeneralPaciente($pdo, $pacienteId); ?>
<section class="cards compact-cards">
  <div class="card"><h3>Paciente</h3><p><?php echo htmlspecialchars($pacienteActual['nombre']); ?></p></div>
  <div class="card"><h3>Tratamientos</h3><p><?php echo (int)($resumen['tratamientos'] ?? 0); ?></p></div>
  <div class="card"><h3>Pagados</h3><p><?php echo (int)($resumen['pagados'] ?? 0); ?></p></div>
  <div class="card"><h3>Pendientes</h3><p><?php echo (int)($resumen['pendientes'] ?? 0); ?></p></div>
  <div class="card"><h3>Total estimado</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)($resumen['estimado'] ?? 0))); ?></p></div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Tratamientos del paciente</h2>
      <p>Se muestra el subtotal de cada tratamiento del paciente y si ya quedó pagado con pagos registrados al paciente.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tratamiento</th>
          <th>Cantidad</th>
          <th>Estado</th>
          <th>¿Pagado?</th>
          <th>Precio</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$itemsPlanGeneral): ?>
          <tr><td colspan="6">No hay tratamientos registrados en el plan general.</td></tr>
        <?php else: ?>
          <?php foreach ($itemsPlanGeneral as $item): ?>
            <?php $estadoPago = $item['estado_pago'] ?? 'pendiente'; ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($item['tratamiento']); ?></strong>
                <?php if (!empty($item['notas'])): ?>
                  <div class="muted-text"><?php echo htmlspecialchars($item['notas']); ?></div>
                <?php endif; ?>
              </td>
              <td><?php echo (int)$item['cantidad']; ?></td>
              <td><span class="badge <?php echo htmlspecialchars($item['estado']); ?>"><?php echo htmlspecialchars(ucfirst($item['estado'])); ?></span></td>
              <td>
                <span class="badge <?php echo htmlspecialchars($estadoPago); ?>">
                  <?php echo $estadoPago === 'pagado' ? 'Sí' : ($estadoPago === 'parcial' ? 'Parcial' : 'No'); ?>
                </span>
                <?php if ($estadoPago === 'parcial' && ($item['abonado'] ?? 0) > 0): ?>
                  <div class="muted-text">Abonado: <?php echo htmlspecialchars(pmFormatoMoneda((float)($item['abonado'] ?? 0))); ?></div>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['precio_unitario'])); ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['subtotal'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<?php include '_layout_bottom.php'; ?>
