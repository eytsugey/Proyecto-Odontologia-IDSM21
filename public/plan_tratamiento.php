<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'doctor', 'secretaria']);
ensureProfessionalModules($pdo);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$planSeleccionadoId = (int)($_GET['plan_id'] ?? 0);

$titulo = 'Plan de tratamiento';
$subtitulo = 'Fases clínicas, servicios planeados y costo estimado por paciente';
$active = 'plan_tratamiento';
$extra_css = ['css/modulos.css'];
include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
$tratamientos = pmTratamientosActivos($pdo);
$citasPaciente = [];
$paciente = null;
$planes = [];
$planActual = null;
$fases = [];
$items = [];
$resumenPaciente = null;

if ($pacienteId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
    $stmt->execute([$pacienteId]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($paciente) {
        $stmt = $pdo->prepare('SELECT id, fecha, hora, estado FROM citas WHERE paciente_id = ? ORDER BY fecha DESC, hora DESC');
        $stmt->execute([$pacienteId]);
        $citasPaciente = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $planes = pmPlanesPaciente($pdo, $pacienteId);
        if ($planSeleccionadoId <= 0 && $planes) {
            $planSeleccionadoId = (int)$planes[0]['id'];
        }

        if ($planSeleccionadoId > 0) {
            $stmt = $pdo->prepare('SELECT * FROM planes_tratamiento WHERE id = ? AND paciente_id = ? LIMIT 1');
            $stmt->execute([$planSeleccionadoId, $pacienteId]);
            $planActual = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($planActual) {
                $stmt = $pdo->prepare('SELECT * FROM plan_tratamiento_fases WHERE plan_id = ? ORDER BY orden ASC, id ASC');
                $stmt->execute([$planSeleccionadoId]);
                $fases = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                $stmt = $pdo->prepare("SELECT pi.*, tc.nombre AS tratamiento
                    FROM plan_tratamiento_items pi
                    JOIN tratamientos_catalogo tc ON tc.id = pi.tratamiento_id
                    WHERE pi.plan_id = ?
                    ORDER BY COALESCE(pi.fase_id, 0) ASC, pi.id ASC");
                $stmt->execute([$planSeleccionadoId]);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        }

        $resumenPaciente = pmResumenFinancieroPaciente($pdo, $pacienteId);
    }
}

$faseMap = [];
foreach ($fases as $fase) {
    $faseMap[(int)$fase['id']] = $fase;
}
?>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Seleccionar paciente</h2>
      <p>Abre el plan clínico de un paciente para dividirlo por fases y costo estimado.</p>
    </div>
  </div>
  <form class="form-grid compact-form compact-gap" method="GET">
    <div class="field field-wide">
      <label>Paciente</label>
      <select name="paciente_id" required>
        <option value="">Selecciona un paciente</option>
        <?php foreach ($pacientes as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === $pacienteId ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field field-wide">
      <button class="btn" type="submit">Abrir plan</button>
    </div>
  </form>
</section>

<?php if ($paciente): ?>
<section class="cards compact-cards">
  <div class="card"><h3>Paciente</h3><p><?php echo htmlspecialchars($paciente['nombre']); ?></p></div>
  <div class="card"><h3>Planes</h3><p><?php echo count($planes); ?></p></div>
  <div class="card"><h3>Total estimado</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)($planActual['total_estimado'] ?? 0))); ?></p></div>
  <div class="card"><h3>Saldo actual</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)($resumenPaciente['saldo'] ?? 0))); ?></p></div>
</section>

<section class="panel two-col-panel equal">
  <div>
    <div class="section-head">
      <div>
        <h2>Crear nuevo plan</h2>
        <p>Úsalo para proponer fases de tratamiento y vincularlo a una cita si quieres.</p>
      </div>
    </div>

    <?php if (isset($_GET['ok'])): ?>
      <div class="notice success">Cambios guardados correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="notice error">Revisa los datos del plan, fase o tratamiento.</div>
    <?php endif; ?>

    <form class="form-grid compact-gap" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
      <input type="hidden" name="action" value="crear_plan">
      <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
      <div class="field span-12">
        <label>Título del plan</label>
        <input type="text" name="titulo" placeholder="Rehabilitación integral, ortodoncia por fases..." required>
      </div>
      <div class="field span-6">
        <label>Cita relacionada</label>
        <select name="cita_id">
          <option value="0">Sin cita específica</option>
          <?php foreach ($citasPaciente as $cita): ?>
            <option value="<?php echo (int)$cita['id']; ?>"><?php echo htmlspecialchars('#' . $cita['id'] . ' · ' . $cita['fecha'] . ' ' . substr($cita['hora'], 0, 5)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-6">
        <label>Estado</label>
        <select name="estado">
          <option value="propuesto">Propuesto</option>
          <option value="aprobado">Aprobado</option>
          <option value="en_proceso">En proceso</option>
          <option value="finalizado">Finalizado</option>
        </select>
      </div>
      <div class="field span-6">
        <label>Diagnóstico</label>
        <textarea name="diagnostico" rows="3"></textarea>
      </div>
      <div class="field span-6">
        <label>Objetivo</label>
        <textarea name="objetivo" rows="3"></textarea>
      </div>
      <div class="field span-12 form-actions-right">
        <button class="btn" type="submit">Crear plan</button>
      </div>
    </form>
  </div>

  <div>
    <div class="section-head">
      <div>
        <h2>Planes del paciente</h2>
        <p>Selecciona uno para administrarlo por fases.</p>
      </div>
    </div>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Plan</th>
            <th>Estado</th>
            <th>Fases</th>
            <th>Items</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$planes): ?>
            <tr><td colspan="5">Aún no hay planes para este paciente.</td></tr>
          <?php else: ?>
            <?php foreach ($planes as $plan): ?>
              <tr>
                <td><a href="plan_tratamiento.php?paciente_id=<?php echo $pacienteId; ?>&plan_id=<?php echo (int)$plan['id']; ?>"><?php echo htmlspecialchars($plan['titulo']); ?></a></td>
                <td><span class="badge <?php echo htmlspecialchars($plan['estado']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $plan['estado']))); ?></span></td>
                <td><?php echo (int)$plan['fases']; ?></td>
                <td><?php echo (int)$plan['items']; ?></td>
                <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$plan['total_estimado'])); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php if ($planActual): ?>
<section class="panel">
  <div class="section-head">
    <div>
      <h2>Plan activo: <?php echo htmlspecialchars($planActual['titulo']); ?></h2>
      <p>Organiza el tratamiento por fases y controla su costo estimado.</p>
    </div>
  </div>
  <form class="form-grid compact-gap" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
    <input type="hidden" name="action" value="actualizar_plan">
    <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
    <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
    <div class="field span-8">
      <label>Título</label>
      <input type="text" name="titulo" value="<?php echo htmlspecialchars($planActual['titulo']); ?>" required>
    </div>
    <div class="field span-2">
      <label>Estado</label>
      <select name="estado">
        <?php foreach (['propuesto','aprobado','en_proceso','finalizado'] as $estadoPlan): ?>
          <option value="<?php echo $estadoPlan; ?>" <?php echo $planActual['estado'] === $estadoPlan ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $estadoPlan))); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field span-2">
      <label>Total estimado</label>
      <input type="text" value="<?php echo htmlspecialchars(pmFormatoMoneda((float)$planActual['total_estimado'])); ?>" disabled>
    </div>
    <div class="field span-6">
      <label>Diagnóstico</label>
      <textarea name="diagnostico" rows="3"><?php echo htmlspecialchars($planActual['diagnostico'] ?? ''); ?></textarea>
    </div>
    <div class="field span-6">
      <label>Objetivo</label>
      <textarea name="objetivo" rows="3"><?php echo htmlspecialchars($planActual['objetivo'] ?? ''); ?></textarea>
    </div>
    <div class="field span-12 form-actions-right">
      <button class="btn" type="submit">Actualizar plan</button>
    </div>
  </form>
</section>

<section class="panel two-col-panel equal">
  <div>
    <div class="section-head">
      <div>
        <h2>Agregar fase</h2>
        <p>Divide el tratamiento en sesiones o bloques clínicos.</p>
      </div>
    </div>
    <form class="form-grid" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
      <input type="hidden" name="action" value="agregar_fase">
      <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
      <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
      <div class="field">
        <label>Nombre de fase</label>
        <input type="text" name="nombre_fase" placeholder="Fase diagnóstica" required>
      </div>
      <div class="field span-2">
        <label>Orden</label>
        <input type="number" name="orden_fase" min="1" value="<?php echo count($fases) + 1; ?>">
      </div>
      <div class="field span-2">
        <label>Estado</label>
        <select name="estado_fase">
          <option value="pendiente">Pendiente</option>
          <option value="en_proceso">En proceso</option>
          <option value="completada">Completada</option>
        </select>
      </div>
      <div class="field span-2">
        <label>Fecha objetivo</label>
        <input type="date" name="fecha_objetivo">
      </div>
      <div class="field span-12">
        <label>Descripción</label>
        <textarea name="descripcion_fase" rows="3"></textarea>
      </div>
      <div class="field span-12 form-actions-right">
        <button class="btn" type="submit">Guardar fase</button>
      </div>
    </form>
  </div>

  <div>
    <div class="section-head">
      <div>
        <h2>Agregar tratamiento al plan</h2>
        <p>Asigna tratamientos al plan y opcionalmente a una fase.</p>
      </div>
    </div>
    <form class="form-grid" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
      <input type="hidden" name="action" value="agregar_item">
      <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
      <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
      <div class="field span-5">
        <label>Tratamiento</label>
        <select name="tratamiento_id" required>
          <option value="">Selecciona</option>
          <?php foreach ($tratamientos as $tratamiento): ?>
            <option value="<?php echo (int)$tratamiento['id']; ?>"><?php echo htmlspecialchars($tratamiento['nombre'] . ' - ' . pmFormatoMoneda((float)$tratamiento['precio_base'])); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-3">
        <label>Fase</label>
        <select name="fase_id">
          <option value="0">Sin fase específica</option>
          <?php foreach ($fases as $fase): ?>
            <option value="<?php echo (int)$fase['id']; ?>"><?php echo htmlspecialchars($fase['orden'] . '. ' . $fase['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-2">
        <label>Cantidad</label>
        <input type="number" name="cantidad" min="1" value="1">
      </div>
      <div class="field span-2">
        <label>Estado</label>
        <select name="estado_item">
          <option value="pendiente">Pendiente</option>
          <option value="autorizado">Autorizado</option>
          <option value="realizado">Realizado</option>
        </select>
      </div>
      <div class="field span-12">
        <label>Notas</label>
        <textarea name="notas_item" rows="3"></textarea>
      </div>
      <div class="field span-12 form-actions-right">
        <button class="btn" type="submit">Agregar tratamiento</button>
      </div>
    </form>
  </div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Fases del plan</h2>
      <p>Administra etapas y progreso del plan activo.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Orden</th>
          <th>Fase</th>
          <th>Estado</th>
          <th>Fecha objetivo</th>
          <th>Editar</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$fases): ?>
          <tr><td colspan="6">Aún no hay fases definidas.</td></tr>
        <?php else: ?>
          <?php foreach ($fases as $fase): ?>
            <tr>
              <td><?php echo (int)$fase['orden']; ?></td>
              <td>
                <strong><?php echo htmlspecialchars($fase['nombre']); ?></strong>
                <div class="muted"><?php echo htmlspecialchars($fase['descripcion'] ?? ''); ?></div>
              </td>
              <td><span class="badge <?php echo htmlspecialchars($fase['estado']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $fase['estado']))); ?></span></td>
              <td><?php echo htmlspecialchars($fase['fecha_objetivo'] ?? ''); ?></td>
              <td>
                <form class="inline-form control-grid" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="actualizar_fase">
                  <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
                  <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
                  <input type="hidden" name="fase_id" value="<?php echo (int)$fase['id']; ?>">
                  <input type="text" name="nombre_fase" value="<?php echo htmlspecialchars($fase['nombre']); ?>" required>
                  <input type="number" name="orden_fase" min="1" value="<?php echo (int)$fase['orden']; ?>">
                  <select name="estado_fase">
                    <?php foreach (['pendiente','en_proceso','completada'] as $estadoFase): ?>
                      <option value="<?php echo $estadoFase; ?>" <?php echo $fase['estado'] === $estadoFase ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $estadoFase))); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="date" name="fecha_objetivo" value="<?php echo htmlspecialchars($fase['fecha_objetivo'] ?? ''); ?>">
                  <input type="text" name="descripcion_fase" value="<?php echo htmlspecialchars($fase['descripcion'] ?? ''); ?>" placeholder="Descripción">
                  <button class="btn-small" type="submit">Guardar</button>
                </form>
              </td>
              <td>
                <form action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="eliminar_fase">
                  <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
                  <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
                  <input type="hidden" name="fase_id" value="<?php echo (int)$fase['id']; ?>">
                  <button class="btn-small secondary" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Tratamientos del plan</h2>
      <p>Puedes ajustar cantidad, fase y avance sin generar factura.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Tratamiento</th>
          <th>Fase</th>
          <th>Cantidad</th>
          <th>Precio</th>
          <th>Subtotal</th>
          <th>Editar</th>
          <th>Eliminar</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr><td colspan="7">Aún no hay tratamientos dentro del plan.</td></tr>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <strong><?php echo htmlspecialchars($item['tratamiento']); ?></strong>
                <div class="muted"><?php echo htmlspecialchars($item['notas'] ?? ''); ?></div>
              </td>
              <td><?php echo htmlspecialchars(isset($faseMap[(int)$item['fase_id']]) ? $faseMap[(int)$item['fase_id']]['nombre'] : 'Sin fase'); ?></td>
              <td><?php echo (int)$item['cantidad']; ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['precio_unitario'])); ?></td>
              <td><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['subtotal'])); ?></td>
              <td>
                <form class="inline-form control-grid" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="actualizar_item">
                  <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
                  <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
                  <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                  <select name="fase_id">
                    <option value="0">Sin fase</option>
                    <?php foreach ($fases as $fase): ?>
                      <option value="<?php echo (int)$fase['id']; ?>" <?php echo (int)$item['fase_id'] === (int)$fase['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($fase['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="number" name="cantidad" min="1" value="<?php echo (int)$item['cantidad']; ?>">
                  <select name="estado_item">
                    <?php foreach (['pendiente','autorizado','realizado'] as $estadoItem): ?>
                      <option value="<?php echo $estadoItem; ?>" <?php echo $item['estado'] === $estadoItem ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($estadoItem)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="notas_item" value="<?php echo htmlspecialchars($item['notas'] ?? ''); ?>" placeholder="Notas">
                  <button class="btn-small" type="submit">Guardar</button>
                </form>
              </td>
              <td>
                <form action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                  <input type="hidden" name="action" value="eliminar_item">
                  <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
                  <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
                  <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                  <button class="btn-small secondary" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php include '_layout_bottom.php'; ?>
