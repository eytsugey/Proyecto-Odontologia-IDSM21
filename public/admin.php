<?php
session_start();
if (!isset($_SESSION["user"])) {
  header("Location: login.php");
  exit;
}

// opcional: solo admin
$rol = strtolower($_SESSION["user"]["rol"] ?? "");
if ($rol !== "administrador" && $rol !== "admin") {
  http_response_code(403);
  die("No autorizado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Administrador | Sistema Odontológico</title>
<link rel="stylesheet" href="css/admin.css">
</head>
<body>

<div class="layout">

  <aside class="sidebar">
    <h2>🦷 DentalApp</h2>
    

    <nav>
      <a class="active">Panel de control</a>
      <a>Gestión de Usuarios</a>
      <a>Pacientes</a>
      <a>Citas</a>
      <a>Tratamientos</a>
      <a>Pagos</a>
      <a>Reportes Financieros</a>
    </nav>
  </aside>

  <main class="main">

<header class="topbar">
  <div>
    <h1>Panel de control</h1>
    <p>Resumen financiero del sistema</p>
  </div>

  <div class="user">
    <?php echo htmlspecialchars($_SESSION["user"]["nombre"]); ?>
    |
    <a href="logout.php" class="logout-btn">Cerrar sesión</a>
  </div>
</header>

    <section class="panel">
      <h2>Resumen de Ingresos</h2>

      <div class="finance-grid">

        <div class="finance-card">
          <h3>Ingresos Semanales</h3>
          <strong>$3,200</strong>
          <span>Semana actual</span>
        </div>

        <div class="finance-card">
          <h3>Ingresos Mensuales</h3>
          <strong>$12,500</strong>
          <span>Mes actual</span>
        </div>

        <div class="finance-card">
          <h3>Pagos en Efectivo</h3>
          <strong>$7,000</strong>
          <span>Acumulado mensual</span>
        </div>

        <div class="finance-card">
          <h3>Pagos con Tarjeta</h3>
          <strong>$5,500</strong>
          <span>Acumulado mensual</span>
        </div>

      </div>
      
    </section>

  </main>

</div>

</body>
</html>
