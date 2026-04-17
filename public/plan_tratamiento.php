<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['admin', 'doctor', 'secretaria']);
ensureProfessionalModules($pdo);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$titulo = 'Plan de tratamiento';
$subtitulo = 'Administra únicamente los tratamientos del paciente y su costo estimado';
$active = 'plan_tratamiento';
$extra_css = ['css/modulos.css', 'css/plan_tratamiento.css'];
include '_layout_top.php';

$pacientes = $pdo->query('SELECT id, nombre FROM pacientes ORDER BY nombre ASC')->fetchAll(PDO::FETCH_ASSOC);
$tratamientos = pmTratamientosActivos($pdo);
$paciente = null;
$planActual = null;
$items = [];
$resumenPaciente = null;

if ($pacienteId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
    $stmt->execute([$pacienteId]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($paciente) {
        $planId = pmObtenerOCrearPlanPacientePorTipo($pdo, $pacienteId, 'general', [
            'titulo' => 'Plan de tratamiento',
            'estado' => 'propuesto',
        ]);

        $stmt = $pdo->prepare('SELECT * FROM planes_tratamiento WHERE id = ? AND paciente_id = ? LIMIT 1');
        $stmt->execute([$planId, $pacienteId]);
        $planActual = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($planActual) {
            $stmt = $pdo->prepare("SELECT pi.*, tc.nombre AS tratamiento
                FROM plan_tratamiento_items pi
                JOIN tratamientos_catalogo tc ON tc.id = pi.tratamiento_id
                WHERE pi.plan_id = ?
                ORDER BY pi.id ASC");
            $stmt->execute([$planId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $resumenPaciente = pmResumenFinancieroPaciente($pdo, $pacienteId);
    }
}
?>

<section class="panel">
  <div class="section-head">
    <div>
      <h2>Seleccionar paciente</h2>
      <p>Abre el plan general del paciente para agregar tratamientos y estimar su costo.</p>
    </div>
  </div>
  <form class="form-grid compact-form compact-gap" method="GET">
    <div class="field span-12">
      <label>Paciente</label>
      <select name="paciente_id" required>
        <option value="">Selecciona un paciente</option>
        <?php foreach ($pacientes as $p): ?>
          <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === $pacienteId ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field span-12 form-actions-right">
      <button class="btn" type="submit">Abrir plan</button>
    </div>
  </form>
</section>

<?php if ($paciente && $planActual): ?>
<div class="plan-shell">
  <section class="cards compact-cards plan-summary-cards">
    <div class="card"><h3>Paciente</h3><p><?php echo htmlspecialchars($paciente['nombre']); ?></p></div>
    <div class="card"><h3>Tratamientos</h3><p><?php echo count($items); ?></p></div>
    <div class="card"><h3>Total estimado</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)$planActual['total_estimado'])); ?></p></div>
    <div class="card"><h3>Saldo actual</h3><p><?php echo htmlspecialchars(pmFormatoMoneda((float)($resumenPaciente['saldo'] ?? 0))); ?></p></div>
  </section>

  <?php if (isset($_GET['ok'])): ?>
    <div class="notice success">Cambios guardados correctamente.</div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="notice error">Revisa los datos enviados.</div>
  <?php endif; ?>

  <section class="panel plan-actions">
    <div class="section-head">
      <div>
        <h2>Agregar tratamiento</h2>
        <p>Asigna tratamientos, cantidad, estado y notas al expediente del paciente.</p>
      </div>
    </div>
    <form class="form-grid compact-form compact-gap" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
      <input type="hidden" name="action" value="agregar_item">
      <input type="hidden" name="return_to" value="plan_tratamiento.php">
      <input type="hidden" name="tipo_plan" value="general">
      <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
      <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
      <div class="field span-7">
        <label>Tratamiento</label>
        <select name="tratamiento_id" required>
          <option value="">Selecciona</option>
          <?php foreach ($tratamientos as $tratamiento): ?>
            <option value="<?php echo (int)$tratamiento['id']; ?>"><?php echo htmlspecialchars($tratamiento['nombre'] . ' - ' . pmFormatoMoneda((float)$tratamiento['precio_base'])); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field span-2">
        <label>Cantidad</label>
        <input type="number" name="cantidad" min="1" value="1">
      </div>
      <div class="field span-3">
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
  </section>

  <section class="panel">
    <div class="section-head">
      <div>
        <h2>Tratamientos del paciente</h2>
        <p>Este listado muestra los tratamientos registrados para el paciente.</p>
      </div>
    </div>
    <div class="plan-table-wrap">
      <table class="table plan-table">
        <thead>
          <tr>
            <th>Tratamiento</th>
            <th>Detalle</th>
            <th>Estado</th>
            <th>Subtotal</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="5" class="plan-empty">Aún no hay tratamientos registrados para este paciente.</td></tr>
          <?php else: ?>
            <?php foreach ($items as $item): ?>
              <tr>
                <td data-label="Tratamiento">
                  <div class="plan-treatment-name">
                    <strong><?php echo htmlspecialchars($item['tratamiento']); ?></strong>
                  </div>
                </td>
                <td data-label="Detalle">
                  <div class="plan-treatment-meta">
                    <span>Cantidad: <?php echo (int)$item['cantidad']; ?></span>
                    <span>Precio unitario: <?php echo htmlspecialchars(pmFormatoMoneda((float)$item['precio_unitario'])); ?></span>
                  </div>
                  <?php if (!empty($item['notas'])): ?>
                    <div class="plan-treatment-notes">Notas: <?php echo htmlspecialchars($item['notas']); ?></div>
                  <?php endif; ?>
                </td>
                <td data-label="Estado"><span class="badge <?php echo htmlspecialchars($item['estado']); ?>"><?php echo htmlspecialchars(ucfirst($item['estado'])); ?></span></td>
                <td data-label="Subtotal"><?php echo htmlspecialchars(pmFormatoMoneda((float)$item['subtotal'])); ?></td>
                <td data-label="Acciones" class="plan-actions-cell">
                  <form class="plan-item-form" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                    <input type="hidden" name="action" value="actualizar_item">
                    <input type="hidden" name="return_to" value="plan_tratamiento.php">
                    <input type="hidden" name="tipo_plan" value="general">
                    <input type="hidden" name="paciente_id" value="<?php echo $pacienteId; ?>">
                    <input type="hidden" name="plan_id" value="<?php echo (int)$planActual['id']; ?>">
                    <input type="hidden" name="item_id" value="<?php echo (int)$item['id']; ?>">
                    <div class="row">
                      <input type="number" name="cantidad" min="1" value="<?php echo (int)$item['cantidad']; ?>">
                      <select name="estado_item">
                        <?php foreach (['pendiente','autorizado','realizado'] as $estadoItem): ?>
                          <option value="<?php echo $estadoItem; ?>" <?php echo $item['estado'] === $estadoItem ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($estadoItem)); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="row single">
                      <input type="text" name="notas_item" value="<?php echo htmlspecialchars($item['notas'] ?? ''); ?>" placeholder="Notas">
                    </div>
                    <button class="btn-small" type="submit">Guardar</button>
                  </form>
                  <form class="plan-delete-form" action="<?php echo htmlspecialchars(appApiUrl('plan_tratamiento.php')); ?>" method="POST">
                    <input type="hidden" name="action" value="eliminar_item">
                    <input type="hidden" name="return_to" value="plan_tratamiento.php">
                    <input type="hidden" name="tipo_plan" value="general">
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
</div>
<?php endif; ?>

<?php include '_layout_bottom.php'; ?>
