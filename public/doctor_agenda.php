<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';

requireLogin(['doctor']);

$titulo = 'Mi agenda';
$subtitulo = 'Citas confirmadas';
$active = 'doctor_agenda';

include '_layout_top.php';

/* 🔥 CITAS BD */
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        p.nombre AS paciente,
        p.telefono,
        c.fecha,
        c.hora,
        c.motivo_consulta
    FROM citas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.estado = 'confirmada'
    ORDER BY c.fecha, c.hora ASC
");
$stmt->execute();
$citas = $stmt->fetchAll();

/* 🔥 EVENTOS BD */
$eventos = [];
foreach($citas as $c){
    $eventos[] = [
        "title" => $c['paciente'],
        "start" => $c['fecha']."T".$c['hora']
    ];
}
?>

<section class="panel">
  <h2>Agenda General</h2>
  <div id="calendar"></div>
</section>

<section class="panel">
<h2 id="tituloDia">Citas por día</h2>

<input type="date" id="fechaSeleccionada">

<table class="table">
<thead>
<tr>
<th>Hora</th>
<th>Paciente</th>
<th>Motivo</th>
<th>Acciones</th>
</tr>
</thead>

<tbody id="tablaCitas">
<tr><td colspan="4">Selecciona una fecha</td></tr>
</tbody>
</table>
</section>

<script>
let todasCitas = <?php echo json_encode($citas); ?>;

document.addEventListener('DOMContentLoaded', function() {

var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {

    initialView: 'timeGridWeek',
    locale: 'es',
    height: 600,

    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    eventSources: [

        {
            events: <?php echo json_encode($eventos); ?>,
            color: '#3498db'
        },

        {
            googleCalendarId: 'b68e1b769381447a8fdd00785965b017325ac55344e6b50409865b57b8b851c2@group.calendar.google.com',
            className: 'gcal-event',
            color: '#27ae60'
        }

    ],

    dateClick: function(info) {
        let fecha = info.dateStr.split("T")[0];
        document.getElementById('fechaSeleccionada').value = fecha;
        mostrarCitas(fecha);
    },

    eventClick: function(info) {
        let fecha = info.event.startStr.split("T")[0];
        document.getElementById('fechaSeleccionada').value = fecha;
        mostrarCitas(fecha);
    }

});

calendar.render();

/* 🔥 HOY */
let hoy = new Date().toISOString().split('T')[0];
document.getElementById('fechaSeleccionada').value = hoy;
mostrarCitas(hoy);

/* FILTRO */
document.getElementById('fechaSeleccionada').addEventListener('change', function(){
    mostrarCitas(this.value);
});

});

/* 🔥 WHATSAPP MULTI ACCIONES */
function enviarWhatsApp(telefono, paciente, fecha, hora, tipo){

    telefono = telefono.replace(/\D/g,'');

    if(!telefono.startsWith('52')){
        telefono = '52' + telefono;
    }

    let mensaje = '';

    switch(tipo){

        case 'recordatorio':
            mensaje = `Hola ${paciente}, te recordamos tu cita el ${fecha} a las ${hora}. 🦷`;
        break;

        case 'confirmada':
            mensaje = `Hola ${paciente}, tu cita del ${fecha} a las ${hora} ha sido CONFIRMADA ✅.`;
        break;

        case 'cancelada':
            mensaje = `Hola ${paciente}, tu cita del ${fecha} a las ${hora} ha sido CANCELADA ❌.`;
        break;

        case 'reprogramada':
            let nuevaFecha = prompt("Nueva fecha (YYYY-MM-DD):", fecha);
            let nuevaHora = prompt("Nueva hora (HH:MM):", hora);

            if(!nuevaFecha || !nuevaHora) return;

            mensaje = `Hola ${paciente}, tu cita ha sido REPROGRAMADA ⏳. Nueva fecha: ${nuevaFecha} a las ${nuevaHora}.`;
        break;
    }

    let url = `https://wa.me/${telefono}?text=${encodeURIComponent(mensaje)}`;
    window.open(url, '_blank');
}

/* 🔥 TABLA DINÁMICA */
function mostrarCitas(fecha){

    let tabla = document.getElementById('tablaCitas');
    let titulo = document.getElementById('tituloDia');

    titulo.innerText = "Citas del " + fecha;
    tabla.innerHTML = '';

    let filtradas = todasCitas.filter(c => c.fecha === fecha);

    if(filtradas.length === 0){
        tabla.innerHTML = '<tr><td colspan="4">No hay citas</td></tr>';
        return;
    }

    filtradas.forEach(c => {

        tabla.innerHTML += `
        <tr>
            <td>${c.hora.substring(0,5)}</td>
            <td>${c.paciente}</td>
            <td>${c.motivo_consulta ?? ''}</td>
            <td>

                <button onclick="enviarWhatsApp('${c.telefono}','${c.paciente}','${c.fecha}','${c.hora}','recordatorio')" class="btn-whatsapp">
                    🔔
                </button>

                <button onclick="enviarWhatsApp('${c.telefono}','${c.paciente}','${c.fecha}','${c.hora}','confirmada')" class="btn-confirmar">
                    ✅
                </button>

                <button onclick="enviarWhatsApp('${c.telefono}','${c.paciente}','${c.fecha}','${c.hora}','reprogramada')" class="btn-reprogramar">
                    ⏳
                </button>

                <button onclick="enviarWhatsApp('${c.telefono}','${c.paciente}','${c.fecha}','${c.hora}','cancelada')" class="btn-cancelar">
                    ❌
                </button>

            </td>
        </tr>
        `;
    });
}
</script>

<style>

.btn-confirmar {
    background: #2ecc71;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-cancelar {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-reprogramar {
    background: #f39c12;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
    cursor: pointer;
}
.btn-whatsapp {
    background: #25D366;
    color: white;
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
    cursor: pointer;
}

</style>

<?php include '_layout_bottom.php'; ?>