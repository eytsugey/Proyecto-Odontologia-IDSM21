<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/schema.php';
require_once __DIR__ . '/../api/config/app.php';

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
              <tr data-cita-id="<?php echo (int)$c['id']; ?>">
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
let actualizandoAgenda = false;

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

function setBotonesFilaDisabled(contenedor, disabled) {
  if (!contenedor) return;

  contenedor.querySelectorAll('button, a.link-action').forEach(function(el) {
    if (el.tagName === 'BUTTON') {
      el.disabled = disabled;
    } else {
      el.style.pointerEvents = disabled ? 'none' : '';
      el.style.opacity = disabled ? '0.7' : '';
    }
  });
}

function renderFilaCita(cita) {
  const hora = String(cita.hora || '').substring(0, 5) || '--:--';
  const motivo = cita.motivo_consulta ? cita.motivo_consulta : 'Consulta general';
  const estado = (cita.estado || 'pendiente').toLowerCase();

  return `
    <tr data-cita-id="${Number(cita.id)}">
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

function mostrarAviso(mensaje, tipo = 'info') {
  const avisoAnterior = document.querySelector('.aviso-flotante');
  if (avisoAnterior) {
    avisoAnterior.remove();
  }

  const aviso = document.createElement('div');
  aviso.className = `aviso-flotante aviso-${tipo}`;
  aviso.textContent = mensaje;

  document.body.appendChild(aviso);

  setTimeout(() => {
    aviso.classList.add('visible');
  }, 10);

  setTimeout(() => {
    aviso.classList.remove('visible');
    setTimeout(() => {
      if (aviso.parentNode) {
        aviso.remove();
      }
    }, 300);
  }, 3500);
}

async function cargarAgenda(fecha, mantenerVista = false) {
  fechaSeleccionada = fecha;
  const titulo = document.getElementById('tituloCitas');
  const tabla = document.getElementById('tablaCitasHoy');

  titulo.textContent = 'Citas del ' + fecha;

  if (!mantenerVista) {
    tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">Cargando...</td></tr>';
  }

  try {
    actualizandoAgenda = true;

    const response = await fetch(`${API_CALENDARIO}?action=agenda&fecha=${encodeURIComponent(fecha)}&_=${Date.now()}`, {
      cache: 'no-store'
    });

    const texto = await response.text();

    let data;
    try {
      data = JSON.parse(texto);
    } catch (e) {
      console.error('Respuesta inválida al cargar agenda:', texto);
      tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">No se pudo cargar la agenda.</td></tr>';
      return;
    }

    if (!Array.isArray(data) || data.length === 0) {
      tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">No hay citas para esta fecha.</td></tr>';
      return;
    }

    tabla.innerHTML = data.map(renderFilaCita).join('');
  } catch (error) {
    console.error('Error al cargar agenda:', error);
    tabla.innerHTML = '<tr><td colspan="5" class="empty-cell">Ocurrió un error al cargar la agenda.</td></tr>';
  } finally {
    actualizandoAgenda = false;
  }
}

async function refrescarTodo() {
  await cargarAgenda(fechaSeleccionada, true);

  if (calendar) {
    calendar.refetchEvents();
  }
}

function construirTooltipEvento(event) {
  const paciente = event.extendedProps?.paciente || event.title || '';
  const motivo = event.extendedProps?.motivo || 'Consulta general';
  const estado = event.extendedProps?.estado || '';
  const hora = event.extendedProps?.hora || '';

  let partes = [];

  if (hora) partes.push(`Hora: ${hora}`);
  if (paciente) partes.push(`Paciente: ${paciente}`);
  if (motivo) partes.push(`Motivo: ${motivo}`);
  if (estado) partes.push(`Estado: ${estado}`);

  return partes.join('\n');
}

function renderEvento(info) {
  const paciente = info.event.extendedProps?.paciente || info.event.title || '';
  const motivo = info.event.extendedProps?.motivo || '';
  const vista = info.view.type;

  if (vista === 'dayGridMonth') {
    return {
      html: `<div class="fc-event-custom fc-event-month" title="${escapeHtml(construirTooltipEvento(info.event)).replace(/\n/g, '&#10;')}">
              <span class="fc-event-paciente">${escapeHtml(paciente)}</span>
            </div>`
    };
  }

  return {
    html: `<div class="fc-event-custom" title="${escapeHtml(construirTooltipEvento(info.event)).replace(/\n/g, '&#10;')}">
            <span class="fc-event-paciente">${escapeHtml(paciente)}</span>
            ${motivo ? `<span class="fc-event-sep"> | </span><span class="fc-event-motivo">${escapeHtml(motivo)}</span>` : ''}
          </div>`
  };
}

async function cambiarEstado(citaId, estado, botonOrigen = null) {
  const fila = botonOrigen ? botonOrigen.closest('tr') : document.querySelector(`tr[data-cita-id="${Number(citaId)}"]`);
  const acciones = fila ? fila.querySelector('.acciones-cita') : null;

  try {
    if (acciones) {
      setBotonesFilaDisabled(acciones, true);
    }

    const body = new URLSearchParams();
    body.append('action', 'estado');
    body.append('cita_id', citaId);
    body.append('estado', estado);

    const response = await fetch(API_CALENDARIO, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
      },
      cache: 'no-store',
      body: body.toString()
    });

    const texto = await response.text();

    let data;
    try {
      data = JSON.parse(texto);
    } catch (e) {
      mostrarAviso('La respuesta del servidor no fue válida.', 'error');
      console.error('Respuesta no JSON:', texto);
      return;
    }

    if (!data.ok) {
      mostrarAviso(data.message || data.google_error || 'No se pudo actualizar la cita.', 'error');
      return;
    }

    let mensaje = 'Estado actualizado correctamente.';
    let tipoAviso = 'ok';

    if (estado === 'confirmada') {
      if (data.sms_ok) {
        mensaje = 'Cita confirmada y SMS enviado correctamente.';
        tipoAviso = 'ok';
      } else if (data.sms_error) {
        const smsError = String(data.sms_error || '');

        if (smsError.includes('21608')) {
          mensaje = 'Cita confirmada, pero el número no está verificado en Twilio trial.';
        } else if (smsError.includes('429') || smsError.toLowerCase().includes('daily messages limit')) {
          mensaje = 'Cita confirmada, pero hoy ya se alcanzó el límite diario de SMS.';
        } else {
          mensaje = 'Cita confirmada, pero no se pudo enviar el SMS.';
        }

        tipoAviso = 'info';
        console.error('Error Twilio:', data.sms_error);
      } else {
        mensaje = 'Cita confirmada correctamente.';
        tipoAviso = 'ok';
      }
    } else if (estado === 'cancelada') {
      mensaje = 'Cita cancelada correctamente.';
      tipoAviso = 'info';
    } else if (estado === 'atendida') {
      mensaje = 'Cita marcada como atendida.';
      tipoAviso = 'ok';
    } else if (estado === 'pendiente') {
      mensaje = 'La cita volvió a estado pendiente.';
      tipoAviso = 'info';
    }

    if (data.google_ok === false && data.google_error) {
      mensaje += ' ' + data.google_error;
      tipoAviso = 'info';
    }

    mostrarAviso(mensaje, tipoAviso);
    await refrescarTodo();
  } catch (error) {
    console.error('Error al cambiar estado:', error);
    mostrarAviso('Ocurrió un error al actualizar la cita.', 'error');
  } finally {
    if (acciones) {
      setBotonesFilaDisabled(acciones, false);
    }
  }
}

document.addEventListener('click', async function(e) {
  const btn = e.target.closest('[data-id][data-estado]');
  if (!btn || actualizandoAgenda) return;

  await cambiarEstado(btn.dataset.id, btn.dataset.estado, btn);
});

document.addEventListener('DOMContentLoaded', function() {
  const calendarEl = document.getElementById('calendar');
  const btnHoy = document.getElementById('btnHoy');

  calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    height: 'auto',
    dayMaxEventRows: 4,
    moreLinkText: 'más',
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      meridiem: false
    },
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
    events: function(fetchInfo, successCallback, failureCallback) {
      const url = `${API_CALENDARIO}?action=eventos&start=${encodeURIComponent(fetchInfo.startStr)}&end=${encodeURIComponent(fetchInfo.endStr)}&_=${Date.now()}`;

      fetch(url, { cache: 'no-store' })
        .then(response => response.json())
        .then(data => successCallback(data))
        .catch(error => {
          console.error('Error al cargar eventos del calendario:', error);
          failureCallback(error);
        });
    },
    eventContent: function(info) {
      return renderEvento(info);
    },
    eventDidMount: function(info) {
      info.el.setAttribute('title', construirTooltipEvento(info.event));
    },
    dateClick: function(info) {
      cargarAgenda(info.dateStr);
    },
    eventClick: function(info) {
      const fechaEvento = info.event.startStr.substring(0, 10);
      cargarAgenda(fechaEvento);
    }
  });

  calendar.render();
  cargarAgenda(fechaSeleccionada);

  btnHoy.addEventListener('click', function() {
    const hoy = new Date().toLocaleDateString('en-CA');
    fechaSeleccionada = hoy;
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

#calendar .fc-daygrid-event {
  border-radius: 6px;
  padding: 1px 4px;
}

#calendar .fc-event-custom {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-weight: 600;
}

#calendar .fc-event-month .fc-event-paciente {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  vertical-align: middle;
}

#calendar .fc-event-motivo {
  font-weight: 500;
}

#calendar .fc-daygrid-event .fc-event-time {
  font-weight: 700;
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
  transition: opacity .2s ease;
}

.link-action {
  background: #eef2ff;
  color: #3730a3;
}

.mini-btn.confirm { background: #dcfce7; color: #166534; }
.mini-btn.cancel { background: #fee2e2; color: #991b1b; }
.mini-btn.done { background: #dbeafe; color: #1d4ed8; }
.btn-secundario { background: #f8fafc; color: #0f172a; }

.mini-btn:disabled {
  opacity: .6;
  cursor: not-allowed;
}

.aviso-flotante {
  position: fixed;
  right: 20px;
  bottom: 20px;
  z-index: 9999;
  min-width: 260px;
  max-width: 420px;
  padding: .9rem 1rem;
  border-radius: 14px;
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.18);
  background: #0f172a;
  color: #fff;
  opacity: 0;
  transform: translateY(14px);
  transition: all .28s ease;
}

.aviso-flotante.visible {
  opacity: 1;
  transform: translateY(0);
}

.aviso-ok {
  background: #166534;
}

.aviso-info {
  background: #1d4ed8;
}

.aviso-error {
  background: #991b1b;
}

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

  .aviso-flotante {
    left: 16px;
    right: 16px;
    bottom: 16px;
    min-width: auto;
    max-width: none;
  }
}
</style>

<?php include '_layout_bottom.php'; ?>