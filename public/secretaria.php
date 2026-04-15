<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/schema.php';

requireLogin(['secretaria', 'doctor']);

$titulo = 'Panel secretaria';
$subtitulo = 'Agenda diaria y calendario de citas';
$active = 'secretaria';

$fechaInicial = date('Y-m-d');

$stmt = $pdo->prepare('
    SELECT c.id, c.paciente_id, c.fecha, c.hora, p.nombre, c.motivo_consulta, LOWER(TRIM(c.estado)) AS estado
    FROM citas c
    INNER JOIN pacientes p ON p.id = c.paciente_id
    WHERE c.fecha = ?
    ORDER BY c.hora ASC, c.id ASC
');
$stmt->execute([$fechaInicial]);
$citasHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($citasHoy as &$cita) {
    $cita['estado'] = citasEstadoNormalizado($cita['estado'] ?? 'pendiente');
}
unset($cita);

include '_layout_top.php';
?>

<section class="panel secretaria-grid">
  <div class="panel-block">
    <div class="panel-head">
      <div>
        <h2>Calendario de citas</h2>
        <p class="panel-text">Haz clic en una fecha para ver y gestionar su agenda.</p>
      </div>
      <div class="estado-legend">
        <span class="legend-item pendiente">Pendiente</span>
        <span class="legend-item confirmada">Confirmada</span>
        <span class="legend-item cancelada">Cancelada</span>
        <span class="legend-item atendida">Atendida</span>
      </div>
    </div>
    <div id="calendar"></div>
  </div>

  <div class="panel-block agenda-block">
    <div id="googleNotice" class="alerta" hidden></div>

    <div class="panel-head agenda-head">
      <div>
        <h2 id="tituloCitas">Citas del <?php echo htmlspecialchars($fechaInicial); ?></h2>
        <p class="panel-text">Vista rápida para secretaria.</p>
      </div>
      <button type="button" class="btn-secundario" id="btnHoy">Ir a hoy</button>
    </div>

    <div class="tabla-wrap">
      <table class="table agenda-table">
        <thead>
          <tr>
            <th>Hora</th>
            <th>Paciente</th>
            <th>Motivo</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tablaCitasHoy">
          <?php if (empty($citasHoy)): ?>
            <tr><td colspan="5" class="empty-cell">No hay citas para esta fecha.</td></tr>
          <?php else: ?>
            <?php foreach ($citasHoy as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars(substr((string)$c['hora'], 0, 5)); ?></td>
                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                <td><?php echo htmlspecialchars($c['motivo_consulta'] ?: 'Consulta general'); ?></td>
                <td><span class="badge <?php echo htmlspecialchars($c['estado']); ?>"><?php echo htmlspecialchars(ucfirst($c['estado'])); ?></span></td>
                <td>
                  <div class="acciones-cita">
                    <a class="link-action" href="citas_paciente.php?cita_id=<?php echo (int)$c['id']; ?>">Editar</a>
                    <button type="button" class="mini-btn confirm" data-id="<?php echo (int)$c['id']; ?>" data-estado="confirmada">Confirmar</button>
                    <button type="button" class="mini-btn cancel" data-id="<?php echo (int)$c['id']; ?>" data-estado="cancelada">Cancelar</button>
                    <button type="button" class="mini-btn done" data-id="<?php echo (int)$c['id']; ?>" data-estado="atendida">Atendida</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.18/locales-all.global.min.js"></script>

<script>
const API_CALENDARIO = <?php echo json_encode(appApiUrl('calendario.php')); ?>;
let fechaSeleccionada = <?php echo json_encode($fechaInicial); ?>;
let calendar;

function escapeHtml(texto) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };

  return String(texto ?? '').replace(/[&<>"']/g, function(m) {
    return map[m];
  });
}

function mostrarAviso(mensaje, tipo = 'ok') {
  const box = document.getElementById('googleNotice');
  if (!box) return;

  box.textContent = mensaje;
  box.className = `alerta alerta-${tipo}`;
  box.hidden = false;

  clearTimeout(box._timer);
  box._timer = setTimeout(() => {
    box.hidden = true;
  }, 5000);
}

function renderFilaCita(cita) {
  const hora = String(cita.hora || '').substring(0, 5) || '--:--';
  const motivo = cita.motivo_consulta ? cita.motivo_consulta : 'Consulta general';
  const estado = (cita.estado || 'pendiente').toLowerCase();

  return `
    <tr>
      <td>${escapeHtml(hora)}</td>
      <td>${escapeHtml(cita.nombre || '')}</td>
      <td>${escapeHtml(motivo)}</td>
      <td><span class="badge ${escapeHtml(estado)}">${escapeHtml(estado.charAt(0).toUpperCase() + estado.slice(1))}</span></td>
      <td>
        <div class="acciones-cita">
          <a class="link-action" href="citas_paciente.php?cita_id=${Number(cita.id)}">Editar</a>
          <button type="button" class="mini-btn confirm" data-id="${Number(cita.id)}" data-estado="confirmada">Confirmar</button>
          <button type="button" class="mini-btn cancel" data-id="${Number(cita.id)}" data-estado="cancelada">Cancelar</button>
          <button type="button" class="mini-btn done" data-id="${Number(cita.id)}" data-estado="atendida">Atendida</button>
        </div>
      </td>
    </tr>
  `;
}

async function cargarAgenda(fecha) {
  fechaSeleccionada = fecha;
  const titulo = document.getElementById('tituloCitas');
  const tabla = document.getElementById('tablaCitasHoy');

  titulo.textContent = 'Citas del ' + fecha;
  tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">Cargando...</td></tr>';

  const response = await fetch(`${API_CALENDARIO}?action=agenda&fecha=${encodeURIComponent(fecha)}`);
  const data = await response.json();

  if (!Array.isArray(data) || data.length === 0) {
    tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">No hay citas para esta fecha.</td></tr>';
    return;
  }

  tabla.innerHTML = data.map(renderFilaCita).join('');
}

async function cambiarEstado(citaId, estado) {
  const body = new URLSearchParams();
  body.append('action', 'estado');
  body.append('cita_id', citaId);
  body.append('estado', estado);

  const response = await fetch(API_CALENDARIO, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
    },
    body: body.toString()
  });

  const data = await response.json();

  if (!data.ok) {
    mostrarAviso(data.message || 'No se pudo actualizar la cita.', 'error');
    return;
  }

  await cargarAgenda(fechaSeleccionada);
  calendar.refetchEvents();

  if (data.google_ok) {
    mostrarAviso(data.google_message || 'La cita se sincronizó correctamente con Google Calendar.', 'ok');
  } else if (data.google_error) {
    mostrarAviso(data.google_error, 'error');
  } else {
    mostrarAviso(data.message || 'La cita se actualizó correctamente.', 'ok');
  }
}

document.addEventListener('click', async function(e) {
  const btn = e.target.closest('[data-id][data-estado]');
  if (!btn) return;

  await cambiarEstado(btn.dataset.id, btn.dataset.estado);
});

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const btnHoy = document.getElementById('btnHoy');

  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    height: 'auto',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    buttonText: {
      today: 'Hoy',
      month: 'Mes',
      week: 'Semana'
    },
    events: `${API_CALENDARIO}?action=eventos`,
    dateClick: function(info) {
      cargarAgenda(info.dateStr);
    },
    eventClick: function(info) {
      cargarAgenda(info.event.startStr.substring(0, 10));
    }
  });

  calendar.render();
  cargarAgenda(fechaSeleccionada);

  btnHoy.addEventListener('click', function() {
    const hoy = new Date().toLocaleDateString('en-CA');
    calendar.today();
    cargarAgenda(hoy);
  });
});
</script>

<style>
.panel.secretaria-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(380px, 0.95fr);
  gap: 1.25rem;
  background: transparent;
  padding: 0;
  box-shadow: none;
}

.panel-block {
  background: #fff;
  border: 1px solid #e7ebf3;
  border-radius: 18px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
  padding: 1.25rem;
  min-width: 0;
}

.alerta {
  padding: 12px 14px;
  border-radius: 10px;
  margin-bottom: 18px;
  font-size: 14px;
  border: 1px solid transparent;
}

.alerta-ok {
  background: #eafaf1;
  color: #1e8449;
  border-color: #b7e4c7;
}

.alerta-error {
  background: #fdecea;
  color: #b03a2e;
  border-color: #f5c6cb;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.panel-head h2 {
  margin: 0 0 .25rem;
}

.panel-text {
  margin: 0;
  color: #64748b;
}

.estado-legend {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
}

.legend-item,
.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: .35rem .75rem;
  font-size: .82rem;
  font-weight: 600;
}

.legend-item.pendiente,
.badge.pendiente { background: #fef3c7; color: #92400e; }
.legend-item.confirmada,
.badge.confirmada { background: #dcfce7; color: #166534; }
.legend-item.cancelada,
.badge.cancelada { background: #fee2e2; color: #991b1b; }
.legend-item.atendida,
.badge.atendida { background: #dbeafe; color: #1d4ed8; }

#calendar {
  min-height: 680px;
}

.tabla-wrap {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: .85rem .75rem;
  border-bottom: 1px solid #edf2f7;
  vertical-align: top;
  text-align: left;
}

.table thead th {
  font-size: .8rem;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: #64748b;
}

.empty-cell {
  text-align: center;
  color: #64748b;
  padding: 1.5rem;
}

.acciones-cita {
  display: flex;
  flex-wrap: wrap;
  gap: .5rem;
}

.link-action,
.mini-btn,
.btn-secundario {
  border: 0;
  border-radius: 10px;
  padding: .5rem .75rem;
  font-size: .85rem;
  cursor: pointer;
  text-decoration: none;
  font-weight: 600;
}

.link-action {
  background: #eef2ff;
  color: #3730a3;
}

.mini-btn.confirm { background: #dcfce7; color: #166534; }
.mini-btn.cancel { background: #fee2e2; color: #991b1b; }
.mini-btn.done { background: #dbeafe; color: #1d4ed8; }
.btn-secundario { background: #f8fafc; color: #0f172a; }

@media (max-width: 1180px) {
  .panel.secretaria-grid {
    grid-template-columns: 1fr;
  }

  #calendar {
    min-height: 580px;
  }
}

@media (max-width: 720px) {
  .panel-block {
    padding: 1rem;
  }

  .panel-head {
    flex-direction: column;
    align-items: stretch;
  }

  #calendar {
    min-height: 500px;
  }
}
</style>

<?php include '_layout_bottom.php'; ?>