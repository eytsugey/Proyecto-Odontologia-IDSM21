<?php
if (!isset($titulo)) { $titulo = 'Sistema'; }
if (!isset($subtitulo)) { $subtitulo = ''; }
if (!isset($active)) { $active = ''; }
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
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <h2>🦷 DentalApp</h2>
    <nav>
      <a class="<?php echo $active==='admin'?'active':''; ?>" href="admin.php">Doctor</a>
      <a class="<?php echo $active==='secretaria'?'active':''; ?>" href="secretaria.php">Secretaria</a>
      <a class="<?php echo $active==='pacientes'?'active':''; ?>" href="pacientes.php">Pacientes</a>
      <a class="<?php echo $active==='citas'?'active':''; ?>" href="citas.php">Citas</a>
      <a class="<?php echo $active==='historia'?'active':''; ?>" href="historia.php">Historia clínica</a>
      <a class="<?php echo $active==='odontograma'?'active':''; ?>" href="odontograma.php">Odontograma</a>
      <a href="../api/auth/logout.php">Cerrar sesión</a>
    </nav>
  </aside>
  <main class="main">
    <header class="topbar">
      <div>
        <h1><?php echo htmlspecialchars($titulo); ?></h1>
        <p><?php echo htmlspecialchars($subtitulo); ?></p>
      </div>
      <div class="user"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></div>
    </header>
