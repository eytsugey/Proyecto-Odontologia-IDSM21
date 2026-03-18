<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($titulo)) { $titulo = 'Sistema'; }
if (!isset($subtitulo)) { $subtitulo = ''; }
if (!isset($active)) { $active = ''; }

$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($titulo); ?></title>
<link rel="stylesheet" href="css/admin.css">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/responsive.css">
<link rel="stylesheet" href="css/pacientes.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <h2>🦷 DentalApp</h2>
    <nav>
      <?php if ($rol === 'doctor'): ?>
        <a class="<?php echo $active === 'admin' ? 'active' : ''; ?>" href="admin.php">Panel doctor</a>
        <a class="<?php echo $active === 'pacientes' ? 'active' : ''; ?>" href="pacientes.php">Pacientes</a>
        <a class="<?php echo $active === 'citas' ? 'active' : ''; ?>" href="citas.php">Citas</a>
      <?php elseif ($rol === 'secretaria'): ?>
        <a class="<?php echo $active === 'secretaria' ? 'active' : ''; ?>" href="secretaria.php">Panel secretaria</a>
        <a class="<?php echo $active === 'pacientes' ? 'active' : ''; ?>" href="pacientes.php">Pacientes</a>
        <a class="<?php echo $active === 'citas' ? 'active' : ''; ?>" href="citas.php">Citas</a>
        <a class="<?php echo $active === 'agendar-cita' ? 'active' : ''; ?>" href="agendar-cita.php">Agendar cita</a>
      <?php else: ?>
        <a href="../index.php">Inicio</a>
      <?php endif; ?>

      <a href="../api/auth/logout.php">Cerrar sesión</a>
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