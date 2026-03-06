<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['doctor']);
$titulo='Panel Doctor'; $subtitulo='Resumen general del consultorio'; $active='admin';
include '_layout_top.php';
$totalPacientes = $pdo->query('SELECT COUNT(*) FROM pacientes')->fetchColumn();
$totalCitas = $pdo->query('SELECT COUNT(*) FROM citas WHERE fecha = CURDATE()')->fetchColumn();
$totalPendientes = $pdo->query('SELECT COUNT(*) FROM citas WHERE estado = "pendiente"')->fetchColumn();
$totalHistorias = $pdo->query('SELECT COUNT(*) FROM historias_clinicas')->fetchColumn();
?>
<section class="cards">
  <div class="card"><h3>Pacientes registrados</h3><p><?php echo $totalPacientes; ?> pacientes en el sistema.</p></div>
  <div class="card"><h3>Citas de hoy</h3><p><?php echo $totalCitas; ?> citas programadas para hoy.</p></div>
  <div class="card"><h3>Pendientes</h3><p><?php echo $totalPendientes; ?> citas pendientes de confirmar.</p></div>
  <div class="card"><h3>Historias clínicas</h3><p><?php echo $totalHistorias; ?> historias capturadas.</p></div>
</section>
<section class="panel">
  <h2>Accesos rápidos</h2>
  <div class="toolbar">
    <a class="btn" href="pacientes.php">Ver pacientes</a>
    <a class="btn" href="citas.php">Gestionar citas</a>
    <a class="btn" href="odontograma.php">Abrir odontograma</a>
  </div>
</section>
<?php include '_layout_bottom.php'; ?>
