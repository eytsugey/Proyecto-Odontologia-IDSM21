<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['doctor', 'admin']);
ensureProfessionalModules($pdo);

$rolActual = normalizarRol($_SESSION['rol'] ?? '');
$titulo = $rolActual === 'admin' ? 'Panel administrador' : 'Panel doctor';
$subtitulo = $rolActual === 'admin' ? 'Resumen general del consultorio, accesos, caja y reportes' : 'Resumen general del consultorio';
$active = 'admin';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$totalPacientes = (int)$pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$totalCitas = (int)$pdo->query('SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()')->fetchColumn();
$totalPendientes = (int)$pdo->query("SELECT COUNT(*) FROM citas WHERE LOWER(estado) = 'pendiente'")->fetchColumn();
$totalHistorias = (int)$pdo->query('SELECT COUNT(*) FROM historias_clinicas')->fetchColumn();
$totalUsuarios = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalCobradoHoy = (float)$pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE fecha_pago = CURDATE() AND estado IN ('pagado','parcial')")->fetchColumn();
$saldoClinico = array_sum(array_map(fn($c) => (float)$c['saldo'], pmCitasCobroPendiente($pdo)));
$totalTratamientos = (int)$pdo->query('SELECT COUNT(*) FROM tratamientos_catalogo WHERE COALESCE(activo,1)=1')->fetchColumn();
?>
<section class="cards compact-cards">
  <div class="card"><h3>Pacientes registrados</h3><p><?php echo $totalPacientes; ?> pacientes en el sistema.</p></div>
  <div class="card"><h3>Citas de hoy</h3><p><?php echo $totalCitas; ?> citas programadas para hoy.</p></div>
  <div class="card"><h3>Pendientes</h3><p><?php echo $totalPendientes; ?> citas pendientes de confirmar.</p></div>
  <div class="card"><h3>Historias clínicas</h3><p><?php echo $totalHistorias; ?> historias capturadas.</p></div>
  <?php if ($rolActual === 'admin'): ?>
    <div class="card"><h3>Usuarios</h3><p><?php echo $totalUsuarios; ?> cuentas registradas.</p></div>
    <div class="card"><h3>Cobrado hoy</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($totalCobradoHoy)); ?></p></div>
    <div class="card"><h3>Saldo clínico</h3><p><?php echo htmlspecialchars(pmFormatoMoneda($saldoClinico)); ?></p></div>
    <div class="card"><h3>Tratamientos</h3><p><?php echo $totalTratamientos; ?> activos en catálogo.</p></div>
  <?php endif; ?>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Centro de control</h2>
      <p>Atajos a los módulos principales del sistema.</p>
    </div>
  </div>
  <div class="quick-links">
    <a class="quick-link-card" href="pacientes.php"><strong>Pacientes</strong><span>Registro, historia clínica, odontograma y seguimiento.</span></a>
    <a class="quick-link-card" href="citas.php"><strong>Citas</strong><span>Alta, confirmación, cambios de fecha y control diario.</span></a>
    <a class="quick-link-card" href="doctor_agenda.php"><strong>Agenda</strong><span>Vista rápida de citas confirmadas por fecha.</span></a>
    <?php if ($rolActual === 'admin'): ?>
      <a class="quick-link-card" href="usuarios.php"><strong>Usuarios</strong><span>Altas, roles, contraseñas y bloqueo de accesos.</span></a>
      <a class="quick-link-card" href="tratamientos.php"><strong>Tratamientos</strong><span>Catálogo con precios base para consulta, cobro y reportes.</span></a>
      <a class="quick-link-card" href="pagos.php"><strong>Pagos</strong><span>Caja, abonos, pendientes y métodos de pago ligados a cita.</span></a>
      <a class="quick-link-card" href="reportes.php"><strong>Reportes</strong><span>Indicadores de operación e ingresos por periodo.</span></a>
      <a class="quick-link-card" href="finanzas_paciente.php"><strong>Estado financiero</strong><span>Saldos, abonos y cargos por paciente sin necesidad de factura.</span></a>
      <a class="quick-link-card" href="plan_tratamiento.php"><strong>Plan por fases</strong><span>Organiza tratamientos por etapas clínicas y costo estimado.</span></a>
      <a class="quick-link-card" href="secretaria.php"><strong>Panel secretaria</strong><span>Operación diaria del mostrador y calendario.</span></a>
    <?php else: ?>
      <a class="quick-link-card" href="odontograma.php"><strong>Odontograma</strong><span>Registro visual de piezas y tratamientos.</span></a>
    <?php endif; ?>
  </div>
</section>
<?php include '_layout_bottom.php'; ?>
