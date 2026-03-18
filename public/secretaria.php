<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['secretaria','doctor']);

/* 🔥 AJAX SIN ARCHIVO EXTRA */
if(isset($_GET['ajax']) && $_GET['ajax'] == 'citas'){
    
    $fecha = $_GET['fecha'] ?? null;

    if(!$fecha){
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare('
    SELECT c.hora, p.nombre, c.estado, c.motivo_consulta
    FROM citas c
    JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.fecha = ?
    ORDER BY c.hora
    ');

    $stmt->execute([$fecha]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

$titulo='Panel Secretaria';
$subtitulo='Registro de pacientes, citas e historia clínica';
$active='secretaria';

include '_layout_top.php';

/* CITAS DE HOY */
$hoy = $pdo->query('
SELECT c.id, p.nombre, c.hora, c.estado
FROM citas c
JOIN pacientes p ON p.id = c.paciente_id
WHERE c.fecha = CURDATE()
ORDER BY c.hora
')->fetchAll();

/* CITAS PARA CALENDARIO */
$citas = $pdo->query('
SELECT 
c.fecha,
c.hora,
GROUP_CONCAT(p.nombre SEPARATOR ", ") AS nombres
FROM citas c
JOIN pacientes p ON p.id = c.paciente_id
GROUP BY c.fecha, c.hora
ORDER BY c.fecha, c.hora
')->fetchAll();
?>

<section class="panel">
<h2>Citas de hoy</h2>

<table class="table">
<thead>
<tr>
<th>Hora</th>
<th>Paciente</th>
<th>Estado</th>
</tr>
</thead>

<tbody>
<?php foreach ($hoy as $c): ?>
<tr>
<td><?php echo substr(htmlspecialchars($c['hora']),0,5); ?></td>
<td><?php echo htmlspecialchars($c['nombre']); ?></td>
<td>
<span class="badge <?php echo htmlspecialchars($c['estado']); ?>">
<?php echo htmlspecialchars($c['estado']); ?>
</span>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="panel">
<h2>Calendario de Citas</h2>
<div id="calendar"></div>
</section>

<!-- 🔥 NUEVA AGENDA -->
<section class="panel">
<h2>Agenda del día</h2>

<table class="table" id="agendaDia">
<thead>
<tr>
<th>Hora</th>
<th>Paciente</th>
<th>Procedimiento</th>
<th>Estado</th>
</tr>
</thead>

<tbody>
<tr>
<td colspan="4">Selecciona un día</td>
</tr>
</tbody>
</table>

</section>

<!-- FULLCALENDAR -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

var calendarEl = document.getElementById('calendar');

var calendar = new FullCalendar.Calendar(calendarEl, {

initialView: 'dayGridMonth',
locale: 'es',

/* 🔥 CLICK EN DÍA */
dateClick: function(info) {
    cargarAgenda(info.dateStr);
},

events: [
<?php
$eventos = [];
foreach ($citas as $c){
$eventos[] = '{
title: "'.addslashes($c['nombres']).' ('.substr($c['hora'],0,5).')",
start: "'.$c['fecha'].'T'.$c['hora'].'"
}';
}
echo implode(",", $eventos);
?>
]

});

calendar.render();

/* 🔥 CARGAR HOY AUTOMÁTICO */
cargarAgenda(new Date().toISOString().split('T')[0]);

});

/* 🔥 FUNCIÓN AJAX */
function cargarAgenda(fecha){

fetch('secretaria.php?ajax=citas&fecha=' + fecha)
.then(res => res.json())
.then(data => {

let tabla = document.querySelector('#agendaDia tbody');
tabla.innerHTML = '';

if(data.length === 0){
tabla.innerHTML = '<tr><td colspan="4">No hay citas</td></tr>';
return;
}

data.forEach(cita => {

tabla.innerHTML += `
<tr>
<td>${cita.hora.substring(0,5)}</td>
<td>${cita.nombre}</td>
<td>${cita.motivo_consulta ?? 'N/A'}</td>
<td><span class="badge ${cita.estado}">${cita.estado}</span></td>
</tr>
`;

});

});
}
</script>

<style>
.panel{
background:#ffffff;
padding:20px;
border-radius:10px;
box-shadow:0 4px 10px rgba(0,0,0,0.08);
margin-bottom:30px;
}

.panel h2{
margin-bottom:15px;
color:#2c3e50;
}

.table{
width:100%;
border-collapse:collapse;
font-family:Arial, sans-serif;
}

.table thead{
background:#3498db;
color:white;
}

.table th, .table td{
padding:10px;
border-bottom:1px solid #eee;
}

.table tr:hover{
background:#f5f7fa;
}

.badge{
padding:5px 10px;
border-radius:20px;
font-size:12px;
font-weight:bold;
}

.badge.pendiente{ background:#f39c12; color:white; }
.badge.confirmada{ background:#27ae60; color:white; }
.badge.cancelada{ background:#e74c3c; color:white; }

#calendar{
max-width:1000px;
margin:30px auto;
}

.fc-daygrid-event{
background:#3498db;
border:none;
padding:3px;
border-radius:5px;
font-size:12px;
}
</style>

<?php include '_layout_bottom.php'; ?>