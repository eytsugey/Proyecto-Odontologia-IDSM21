<?php
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../middleware/auth.php';

validarSesion('secretaria');

header('Content-Type: application/json');

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel Secretaria</title>
<link rel="stylesheet" href="secretaria.css">
</head>
<body>

<h2>Panel de Secretaria</h2>

<table border="1" width="100%">
  <thead>
    <tr>
      <th>Paciente</th>
      <th>Fecha</th>
      <th>Hora</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody id="tablaCitas"></tbody>
</table>

<script>
async function cargarCitas() {
  const res = await fetch('../api/secretaria.php');
  const citas = await res.json();

  const tabla = document.getElementById('tablaCitas');
  tabla.innerHTML = '';

  citas.forEach(c => {
    tabla.innerHTML += `
      <tr>
        <td>${c.nombre}</td>
        <td>${c.fecha}</td>
        <td>${c.hora}</td>
        <td>
          <button onclick="actualizar(${c.id_cita}, 'confirmada')">Aceptar</button>
          <button onclick="actualizar(${c.id_cita}, 'cancelada')">Cancelar</button>
        </td>
      </tr>
    `;
  });
}

async function actualizar(id, estado) {
  await fetch('../api/secretaria.php', {
    method: 'PUT',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({ id_cita: id, estado })
  });
  cargarCitas();
}

cargarCitas();
</script>

</body>
</html>