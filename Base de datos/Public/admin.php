<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    header("Location: login.html");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Administrador</title>
<link rel="stylesheet" href="admin.css">
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
        <h1>Administrador</h1>
        <p>Gestión total del sistema</p>
      </div>
      <div class="user">
        <?= $_SESSION['usuario']['nombre'] ?>
      </div>
    </header>

    <section class="panel">
      <h2>Reporte Financiero</h2>

      <div class="finance" id="finanzas">
        Cargando datos...
      </div>
    </section>

  </main>

</div>

<script>
fetch('/api/admin.php')
  .then(res => res.json())
  .then(data => {
    const cont = document.getElementById('finanzas');
    cont.innerHTML = '';

    data.forEach(item => {
      cont.innerHTML += `
        <div>
          <strong>$${item.total}</strong>
          <span>${item.metodo_pago}</span>
        </div>
      `;
    });
  });
</script>

</body>
</html>