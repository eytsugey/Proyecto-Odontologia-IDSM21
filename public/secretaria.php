<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
requireLogin(['secretaria','doctor']);

/* 🔥 CAMBIAR ESTADO (DEBE IR ARRIBA) */
if(isset($_GET['accion']) && isset($_GET['id'])){

    $id = $_GET['id'];
    $accion = $_GET['accion'];

    if($accion == 'confirmar'){
        $estado = 'confirmada';
    } elseif($accion == 'cancelar'){
        $estado = 'cancelada';
    } else {
        header("Location: secretaria.php");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $id]);

    header("Location: secretaria.php");
    exit;
}

/* 🔥 AJAX */
if(isset($_GET['ajax']) && $_GET['ajax'] == 'citas'){
    
    $fecha = $_GET['fecha'] ?? null;

    if(!$fecha){
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare('
    SELECT c.id, c.fecha, c.hora, p.nombre, c.estado, c.motivo_consulta
    FROM citas c
    JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.fecha = ?
    ORDER BY c.hora
    ');

    $stmt->execute([$fecha]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* 🔥 CITAS HOY */
$hoy = $pdo->query('
SELECT c.id, c.fecha, c.hora, p.nombre, c.estado, c.motivo_consulta
FROM citas c
JOIN pacientes p ON p.id = c.paciente_id
WHERE c.fecha = CURDATE()
ORDER BY c.hora
')->fetchAll();

/* 🔥 CALENDARIO */
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

$titulo='Panel Secretaria';
$subtitulo='Registro de pacientes, citas e historia clínica';
$active='secretaria';

include '_layout_top.php';
?>

<section class="panel">
<h2 id="tituloCitas">Citas de hoy</h2>

<table class="table">
<thead>
<tr>
<th>Paciente</th>
<th>Fecha</th>
<th>Hora</th>
<th>Motivo</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>

<tbody id="tablaCitasHoy">
<?php foreach ($hoy as $c): ?>
<tr>
<td><?php echo htmlspecialchars($c['nombre']); ?></td>
<td><?php echo htmlspecialchars($c['fecha']); ?></td>
<td><?php echo substr(htmlspecialchars($c['hora']),0,5); ?></td>
<td><?php echo htmlspecialchars($c['motivo_consulta'] ?? 'N/A'); ?></td>

<td>
<span class="badge <?php echo htmlspecialchars($c['estado']); ?>">
<?php echo htmlspecialchars($c['estado']); ?>
</span>
</td>

<td>
<a href="citas.php?id=<?php echo $c['id']; ?>">Editar</a> |
<a href="secretaria.php?accion=confirmar&id=<?php echo $c['id']; ?>">Confirmar</a> |
<a href="secretaria.php?accion=cancelar&id=<?php echo $c['id']; ?>">Cancelar</a>
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

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

var calendarEl = document.getElementById('calendar');

var calendar = new FullCalendar.Calendar(calendarEl, {

initialView: 'dayGridMonth',
locale: 'es',

dateClick: function(info) {
    cargarAgenda(info.dateStr);
},

events: [
<?php
$eventos = [];
foreach ($citas as $c){
$eventos[] = '{
title: "'.addslashes($c['nombres']).'",
start: "'.$c['fecha'].'T'.$c['hora'].'"
}';
}
echo implode(",", $eventos);
?>
]

});

calendar.render();

/* 🔥 CARGAR HOY */
cargarAgenda(new Date().toISOString().split('T')[0]);

});

/* 🔥 AJAX */
function cargarAgenda(fecha){

fetch('secretaria.php?ajax=citas&fecha=' + fecha)
.then(res => res.json())
.then(data => {

let tabla = document.querySelector('#tablaCitasHoy');
let titulo = document.getElementById('tituloCitas');

tabla.innerHTML = '';
titulo.innerText = 'Citas del ' + fecha;

if(data.length === 0){
tabla.innerHTML = '<tr><td colspan="6">No hay citas</td></tr>';
return;
}

data.forEach(cita => {

tabla.innerHTML += `
<tr>
<td>${cita.nombre}</td>
<td>${cita.fecha}</td>
<td>${cita.hora.substring(0,5)}</td>
<td>${cita.motivo_consulta ?? 'N/A'}</td>
<td><span class="badge ${cita.estado}">${cita.estado}</span></td>
<td>
<a href="citas.php?id=${cita.id}">Editar</a> |
<a href="secretaria.php?accion=confirmar&id=${cita.id}">Confirmar</a> |
<a href="secretaria.php?accion=cancelar&id=${cita.id}">Cancelar</a>
</td>
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

.table{
width:100%;
border-collapse:collapse;
}

.table thead{
background:#3498db;
color:white;
}

.table th, .table td{
padding:10px;
border-bottom:1px solid #eee;
}

.badge{
padding:5px 10px;
border-radius:20px;
font-size:12px;
}

.badge.pendiente{ background:#f39c12; color:white; }
.badge.confirmada{ background:#27ae60; color:white; }
.badge.cancelada{ background:#e74c3c; color:white; }

#calendar{
max-width:1000px;
margin:30px auto;
}
</style>

<?php include '_layout_bottom.php'; ?>