<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'doctor']);
ensureProfessionalModules($pdo);

$titulo = 'Reportes';
$subtitulo = 'Indicadores operativos y financieros del consultorio';
$active = 'reportes';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT 
    COUNT(*) AS total_citas,
    SUM(CASE WHEN LOWER(estado) = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
    SUM(CASE WHEN LOWER(estado) = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
    SUM(CASE WHEN LOWER(estado) = 'cancelada' THEN 1 ELSE 0 END) AS canceladas
    FROM citas
    WHERE fecha BETWEEN ? AND ?");
$stmt->execute([$desde, $hasta]);
$resumenCitas = $stmt->fetch() ?: ['total_citas'=>0,'confirmadas'=>0,'pendientes'=>0,'canceladas'=>0];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pacientes WHERE DATE(COALESCE(fecha_nacimiento, CURDATE())) IS NOT NULL");
$stmt->execute();
$totalPacientes = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) AS total_ingresos,
    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) AS total_pendiente,
    SUM(CASE WHEN metodo_pago = 'efectivo' THEN monto ELSE 0 END) AS efectivo,
    SUM(CASE WHEN metodo_pago = 'tarjeta' THEN monto ELSE 0 END) AS tarjeta,
    SUM(CASE WHEN metodo_pago = 'transferencia' THEN monto ELSE 0 END) AS transferencia
    FROM pagos
    WHERE fecha_pago BETWEEN ? AND ?");
$stmt->execute([$desde, $hasta]);
$finanzas = $stmt->fetch() ?: ['total_ingresos'=>0,'total_pendiente'=>0,'efectivo'=>0,'tarjeta'=>0,'transferencia'=>0];

$stmt = $pdo->prepare("SELECT DATE(fecha_pago) AS fecha, COALESCE(SUM(monto),0) AS total
    FROM pagos
    WHERE fecha_pago BETWEEN ? AND ?
    GROUP BY DATE(fecha_pago)
    ORDER BY fecha ASC");
$stmt->execute([$desde, $hasta]);
$ingresosDiarios = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT concepto, COUNT(*) AS movimientos, COALESCE(SUM(monto),0) AS total
    FROM pagos
    WHERE fecha_pago BETWEEN ? AND ?
    GROUP BY concepto
    ORDER BY total DESC
    LIMIT 8");
$stmt->execute([$desde, $hasta]);
$topConceptos = $stmt->fetchAll();


$stmt = $pdo->prepare("SELECT tc.nombre, COUNT(*) AS veces, COALESCE(SUM(ct.subtotal),0) AS total
    FROM cita_tratamientos ct
    JOIN tratamientos_catalogo tc ON tc.id = ct.tratamiento_id
    JOIN citas c ON c.id = ct.cita_id
    WHERE c.fecha BETWEEN ? AND ?
    GROUP BY tc.nombre
    ORDER BY total DESC, veces DESC
    LIMIT 8");
$stmt->execute([$desde, $hasta]);
$topTratamientos = $stmt->fetchAll();

$saldoClinico = array_sum(array_map(fn($c) => (float)$c['saldo'], pmCitasCobroPendiente($pdo)));

$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$finSemana = date('Y-m-d', strtotime('sunday this week'));
$inicioMes = date('Y-m-01');
$finMes = date('Y-m-t');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago BETWEEN ? AND ?");
$stmt->execute([$inicioSemana, $finSemana]);
$ingresoSemanalActual = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago BETWEEN ? AND ?");
$stmt->execute([$inicioMes, $finMes]);
$ingresoMensualActual = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT YEAR(fecha_pago) AS anio, WEEK(fecha_pago, 1) AS semana, MIN(DATE(fecha_pago)) AS inicio_semana, MAX(DATE(fecha_pago)) AS fin_semana, COALESCE(SUM(monto),0) AS total
    FROM pagos
    WHERE fecha_pago BETWEEN ? AND ?
    GROUP BY YEAR(fecha_pago), WEEK(fecha_pago, 1)
    ORDER BY YEAR(fecha_pago) DESC, WEEK(fecha_pago, 1) DESC
    LIMIT 8");
$stmt->execute([$desde, $hasta]);
$ingresosSemanales = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT DATE_FORMAT(fecha_pago, '%Y-%m') AS periodo, COALESCE(SUM(monto),0) AS total
    FROM pagos
    WHERE fecha_pago BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(fecha_pago, '%Y-%m')
    ORDER BY periodo DESC
    LIMIT 8");
$stmt->execute([$desde, $hasta]);
$ingresosMensuales = $stmt->fetchAll();

?>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Rango del reporte</h2>
      <p>Consulta actividad clínica y financiera por periodo.</p>
    </div>
  </div>
  <form class="form-grid compact-form" method="GET">
    <div class="field">
      <label>Desde</label>
      <input type="date" name="desde" value="<?php echo htmlspecialchars($desde); ?>" required>
    </div>
    <div class="field">
      <label>Hasta</label>
      <input type="date" name="hasta" value="<?php echo htmlspecialchars($hasta); ?>" required>
    </div>
    <div class="field field-wide">
      <button class="btn" type="submit">Actualizar reporte</button>
    </div>
  </form>
</section>

<section class="cards compact-cards">
  <div class="card"><h3>Citas del periodo</h3><p><?php echo (int)$resumenCitas['total_citas']; ?></p></div>
  <div class="card"><h3>Confirmadas</h3><p><?php echo (int)$resumenCitas['confirmadas']; ?></p></div>
  <div class="card"><h3>Pacientes registrados</h3><p><?php echo $totalPacientes; ?></p></div>
  <div class="card"><h3>Ingresos</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$finanzas['total_ingresos'])); ?></p></div>
  <div class="card"><h3>Ingreso semanal</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($ingresoSemanalActual)); ?></p></div>
  <div class="card"><h3>Ingreso mensual</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($ingresoMensualActual)); ?></p></div>
  <div class="card"><h3><a href="finanzas_paciente.php">Estado por paciente</a></h3><p>Consulta saldos individuales y movimientos.</p></div>
</section>

<section class="panel two-col-panel">
  <div>
    <h2>Indicadores clínicos</h2>
    <div class="metric-list">
      <div class="metric-item"><span>Pendientes</span><strong><?php echo (int)$resumenCitas['pendientes']; ?></strong></div>
      <div class="metric-item"><span>Canceladas</span><strong><?php echo (int)$resumenCitas['canceladas']; ?></strong></div>
      <div class="metric-item"><span>Ingresos pendientes</span><strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$finanzas['total_pendiente'])); ?></strong></div>
      <div class="metric-item"><span>Efectivo</span><strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$finanzas['efectivo'])); ?></strong></div>
      <div class="metric-item"><span>Tarjeta</span><strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$finanzas['tarjeta'])); ?></strong></div>
      <div class="metric-item"><span>Transferencia</span><strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$finanzas['transferencia'])); ?></strong></div>
      <div class="metric-item"><span>Saldo clínico</span><strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$saldoClinico)); ?></strong></div>
    </div>
  </div>
  <div>
    <h2>Ingresos diarios</h2>
    <div class="simple-bars">
      <?php if (!$ingresosDiarios): ?>
        <p class="muted">No hay movimientos en el rango seleccionado.</p>
      <?php else: ?>
        <?php $max = max(array_map(fn($i) => (float)$i['total'], $ingresosDiarios)); ?>
        <?php foreach ($ingresosDiarios as $item): ?>
          <?php $width = $max > 0 ? max(8, (int)(((float)$item['total'] / $max) * 100)) : 0; ?>
          <div class="bar-row">
            <span><?php echo htmlspecialchars($item['fecha']); ?></span>
            <div class="bar-track"><div class="bar-fill" style="width: <?php echo $width; ?>%"></div></div>
            <strong><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['total'])); ?></strong>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Conceptos con mayor ingreso</h2>
      <p>Sirve para detectar los servicios más rentables o frecuentes.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Concepto</th>
          <th>Movimientos</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$topConceptos): ?>
          <tr><td colspan="3">No hay datos para este periodo.</td></tr>
        <?php else: ?>
          <?php foreach ($topConceptos as $concepto): ?>
            <tr>
              <td><?php echo htmlspecialchars($concepto['concepto']); ?></td>
              <td><?php echo (int)$concepto['movimientos']; ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$concepto['total'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>


<section class="panel two-col-panel">
  <div>
    <h2>Ingresos semanales</h2>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Semana</th>
            <th>Rango</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$ingresosSemanales): ?>
            <tr><td colspan="3">No hay ingresos semanales en el rango seleccionado.</td></tr>
          <?php else: ?>
            <?php foreach ($ingresosSemanales as $item): ?>
              <tr>
                <td>Semana <?php echo (int)$item['semana']; ?> / <?php echo (int)$item['anio']; ?></td>
                <td><?php echo htmlspecialchars(($item['inicio_semana'] ?? '') . ' al ' . ($item['fin_semana'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['total'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div>
    <h2>Ingresos mensuales</h2>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Periodo</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$ingresosMensuales): ?>
            <tr><td colspan="2">No hay ingresos mensuales en el rango seleccionado.</td></tr>
          <?php else: ?>
            <?php foreach ($ingresosMensuales as $item): ?>
              <tr>
                <td><?php echo htmlspecialchars($item['periodo']); ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['total'])); ?></td>
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
      <h2>Tratamientos con mayor ingreso</h2>
      <p>Resumen de servicios más vendidos o con mayor facturación en el periodo.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tratamiento</th>
          <th>Veces</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$topTratamientos): ?>
          <tr><td colspan="3">No hay tratamientos registrados para este periodo.</td></tr>
        <?php else: ?>
          <?php foreach ($topTratamientos as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['nombre']); ?></td>
              <td><?php echo (int)$item['veces']; ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['total'])); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include '_layout_bottom.php'; ?>

