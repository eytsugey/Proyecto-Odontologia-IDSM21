<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../api/config/app.php';
require_once __DIR__ . '/../api/middleware/auth.php';

if (!isset($titulo)) { $titulo = 'Sistema'; }
if (!isset($subtitulo)) { $subtitulo = ''; }
if (!isset($active)) { $active = ''; }
if (!isset($extra_css) || !is_array($extra_css)) { $extra_css = []; }

$rol = normalizarRol($_SESSION['rol'] ?? '');
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$bodyClass = trim('app-layout ' . ($active ? 'page-' . preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($active)) : ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?></title>
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/responsive.css">
<?php foreach ($extra_css as $cssFile): ?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
<?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
<div class="layout">
  <aside class="sidebar">
    <h2>🦷 DentalApp</h2>
    <nav>
      <?php if ($rol === 'admin'): ?>
        <a class="<?php echo $active === 'admin' ? 'active' : ''; ?>" href="admin.php">Panel admin</a>
        <a class="<?php echo $active === 'usuarios' ? 'active' : ''; ?>" href="usuarios.php">Usuarios</a>
        <a class="<?php echo $active === 'pacientes' ? 'active' : ''; ?>" href="pacientes.php">Pacientes</a>
        <a class="<?php echo $active === 'citas' ? 'active' : ''; ?>" href="citas.php">Citas</a>
        <a class="<?php echo $active === 'doctor_agenda' ? 'active' : ''; ?>" href="doctor_agenda.php">Agenda doctor</a>
        <a class="<?php echo $active === 'tratamientos' ? 'active' : ''; ?>" href="tratamientos.php">Tratamientos</a>
        <a class="<?php echo $active === 'plan_tratamiento' ? 'active' : ''; ?>" href="plan_tratamiento.php">Plan por fases</a>
        <a class="<?php echo $active === 'pagos' ? 'active' : ''; ?>" href="pagos.php">Pagos</a>
        <a class="<?php echo $active === 'finanzas_paciente' ? 'active' : ''; ?>" href="finanzas_paciente.php">Estado financiero</a>
        <a class="<?php echo $active === 'reportes' ? 'active' : ''; ?>" href="reportes.php">Reportes</a>
        <a class="<?php echo $active === 'secretaria' ? 'active' : ''; ?>" href="secretaria.php">Panel secretaria</a>
      <?php elseif ($rol === 'doctor'): ?>
        <a class="<?php echo $active === 'admin' ? 'active' : ''; ?>" href="admin.php">Panel doctor</a>
        <a class="<?php echo $active === 'pacientes' ? 'active' : ''; ?>" href="pacientes.php">Pacientes</a>
        <a class="<?php echo $active === 'citas' ? 'active' : ''; ?>" href="citas.php">Citas</a>
        <a class="<?php echo $active === 'doctor_agenda' ? 'active' : ''; ?>" href="doctor_agenda.php">Agenda</a>
        <a class="<?php echo $active === 'tratamientos' ? 'active' : ''; ?>" href="tratamientos.php">Tratamientos</a>
        <a class="<?php echo $active === 'plan_tratamiento' ? 'active' : ''; ?>" href="plan_tratamiento.php">Plan por fases</a>
        <a class="<?php echo $active === 'reportes' ? 'active' : ''; ?>" href="reportes.php">Reportes</a>
      <?php elseif ($rol === 'secretaria'): ?>
        <a class="<?php echo $active === 'secretaria' ? 'active' : ''; ?>" href="secretaria.php">Panel secretaria</a>
        <a class="<?php echo $active === 'pacientes' ? 'active' : ''; ?>" href="pacientes.php">Pacientes</a>
        <a class="<?php echo $active === 'citas' ? 'active' : ''; ?>" href="citas.php">Citas</a>
        <a class="<?php echo $active === 'plan_tratamiento' ? 'active' : ''; ?>" href="plan_tratamiento.php">Plan por fases</a>
        <a class="<?php echo $active === 'pagos' ? 'active' : ''; ?>" href="pagos.php">Pagos</a>
        <a class="<?php echo $active === 'finanzas_paciente' ? 'active' : ''; ?>" href="finanzas_paciente.php">Estado financiero</a>
        <a class="<?php echo $active === 'agendar-cita' ? 'active' : ''; ?>" href="agendar-cita.php">Agendar cita</a>
      <?php else: ?>
        <a href="index.php">Inicio</a>
      <?php endif; ?>

      <a href="<?php echo htmlspecialchars(appApiUrl('auth/logout.php')); ?>">Cerrar sesión</a>
    </nav>
  </aside>

  <main class="main">
    <header class="topbar">
      <div>
        <h1><?php echo htmlspecialchars($titulo); ?></h1>
        <p><?php echo htmlspecialchars($subtitulo); ?></p>
      </div>
      <div class="user">
        <?php echo htmlspecialchars($nombre); ?><?php echo $rol ? ' - ' . htmlspecialchars(ucfirst($rol)) : ''; ?>
      </div>
    </header>
