<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['secretaria','doctor']);
$titulo='Panel Secretaria'; $subtitulo='Registro de pacientes, citas e historia clínica'; $active='secretaria';
include '_layout_top.php';
$hoy = $pdo->query('SELECT c.id, p.nombre, c.hora, c.estado FROM citas c JOIN pacientes p ON p.id = c.paciente_id WHERE c.fecha = CURDATE() ORDER BY c.hora')->fetchAll();
?>
<section class="panel">
  <h2>Citas de hoy</h2>
  <table class="table">
    <thead><tr><th>Hora</th><th>Paciente</th><th>Estado</th></tr></thead>
    <tbody>
      <?php foreach ($hoy as $c): ?>
      <tr><td><?php echo htmlspecialchars($c['hora']); ?></td><td><?php echo htmlspecialchars($c['nombre']); ?></td><td><span class="badge <?php echo htmlspecialchars($c['estado']); ?>"><?php echo htmlspecialchars($c['estado']); ?></span></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php include '_layout_bottom.php'; ?>
