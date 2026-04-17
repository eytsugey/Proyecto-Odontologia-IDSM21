<?php
session_start();
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/middleware/auth.php';
require_once __DIR__ . '/../api/helpers/professional_modules.php';
requireLogin(['doctor', 'secretaria']);
ensureProfessionalModules($pdo);

$pacienteId = (int)($_GET['paciente_id'] ?? 0);
if (!$pacienteId) {
    header('Location: pacientes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$pacienteId]);
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    header('Location: pacientes.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM historias_clinicas WHERE paciente_id = ? LIMIT 1');
$stmt->execute([$pacienteId]);
$historia = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$tratamientosRealizados = pmTratamientosRealizadosHistoriaPaciente($pdo, $pacienteId);

$titulo = 'Historia clínica';
$subtitulo = 'Registro clínico de ' . $paciente['nombre'];
$active = 'pacientes';
$ok = isset($_GET['ok']);

function checkedField(array $historia, string $campo): string {
    return !empty($historia[$campo]) ? 'checked' : '';
}

function valueField(array $historia, string $campo): string {
    return htmlspecialchars($historia[$campo] ?? '');
}

function disabledField(array $historia, string $campo): string {
    return empty($historia[$campo]) ? 'disabled' : '';
}

include '_layout_top.php';
?>

<?php if ($ok): ?>
<div class="notice success">Historia clínica guardada.</div>
<?php endif; ?>

<section class="panel panel-winforms">
  <div class="toolbar">
    <h2><?php echo htmlspecialchars($paciente['nombre']); ?></h2>
    <a class="btn-secondary" href="pacientes.php">Volver a pacientes</a>
  </div>

  <form action="../api/historia.php" method="POST" class="historia-form">
    <input type="hidden" name="paciente_id" value="<?php echo (int)$pacienteId; ?>">

    <div class="wf-layout">

      <fieldset class="wf-group">
        <legend>Antecedentes médicos</legend>

        <div class="wf-grid">
          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="alergico_med_comida" <?php echo checkedField($historia, 'alergico_med_comida'); ?>>
                <span>Alérgico a medicamentos o comida</span>
              </label>
            </div>
            <label>¿Cuál alergia?</label>
            <input
              type="text"
              name="cual_alergia"
              data-toggle="alergico_med_comida"
              value="<?php echo valueField($historia, 'cual_alergia'); ?>"
              <?php echo disabledField($historia, 'alergico_med_comida'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="intervencion" <?php echo checkedField($historia, 'intervencion'); ?>>
                <span>Intervención quirúrgica</span>
              </label>
            </div>
            <label>¿Cuál intervención?</label>
            <input
              type="text"
              name="cual_intervencion"
              data-toggle="intervencion"
              value="<?php echo valueField($historia, 'cual_intervencion'); ?>"
              <?php echo disabledField($historia, 'intervencion'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="reac_anestesia" <?php echo checkedField($historia, 'reac_anestesia'); ?>>
                <span>Reacción a anestesia</span>
              </label>
            </div>
            <label>¿Cuál reacción?</label>
            <input
              type="text"
              name="cual_reaccion"
              data-toggle="reac_anestesia"
              value="<?php echo valueField($historia, 'cual_reaccion'); ?>"
              <?php echo disabledField($historia, 'reac_anestesia'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="coagulacion_sang" <?php echo checkedField($historia, 'coagulacion_sang'); ?>>
                <span>Coagulación sanguínea</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="diabetes" <?php echo checkedField($historia, 'diabetes'); ?>>
                <span>Diabetes</span>
              </label>
            </div>
            <label>Medicamento para diabetes</label>
            <input
              type="text"
              name="med_diabetes"
              data-toggle="diabetes"
              value="<?php echo valueField($historia, 'med_diabetes'); ?>"
              <?php echo disabledField($historia, 'diabetes'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="pres_alta" <?php echo checkedField($historia, 'pres_alta'); ?>>
                <span>Presión alta</span>
              </label>
            </div>
            <label>Medicamento para presión alta</label>
            <input
              type="text"
              name="med_pres_alta"
              data-toggle="pres_alta"
              value="<?php echo valueField($historia, 'med_pres_alta'); ?>"
              <?php echo disabledField($historia, 'pres_alta'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="pres_baja" <?php echo checkedField($historia, 'pres_baja'); ?>>
                <span>Presión baja</span>
              </label>
            </div>
            <label>Medicamento para presión baja</label>
            <input
              type="text"
              name="med_pres_baja"
              data-toggle="pres_baja"
              value="<?php echo valueField($historia, 'med_pres_baja'); ?>"
              <?php echo disabledField($historia, 'pres_baja'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="enf_venereas" <?php echo checkedField($historia, 'enf_venereas'); ?>>
                <span>Enfermedades venéreas</span>
              </label>
            </div>
            <label>¿Cuáles?</label>
            <input
              type="text"
              name="cuales_enf_ven"
              data-toggle="enf_venereas"
              value="<?php echo valueField($historia, 'cuales_enf_ven'); ?>"
              <?php echo disabledField($historia, 'enf_venereas'); ?>
            >
          </div>

          <div class="wf-item2">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="hepatitis" <?php echo checkedField($historia, 'hepatitis'); ?>>
                <span>Hepatitis</span>
              </label>
            </div>
            <label>Tipo de hepatitis</label>
            <input
              type="text"
              name="tipo_hepatitis"
              data-toggle="hepatitis"
              value="<?php echo valueField($historia, 'tipo_hepatitis'); ?>"
              <?php echo disabledField($historia, 'hepatitis'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="asma" <?php echo checkedField($historia, 'asma'); ?>>
                <span>Asma</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="enfermedad" <?php echo checkedField($historia, 'enfermedad'); ?>>
                <span>Otra enfermedad</span>
              </label>
            </div>
            <label>¿Cuál enfermedad?</label>
            <input
              type="text"
              name="cuales_enfermedad"
              data-toggle="enfermedad"
              value="<?php echo valueField($historia, 'cuales_enfermedad'); ?>"
              <?php echo disabledField($historia, 'enfermedad'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="drogas" <?php echo checkedField($historia, 'drogas'); ?>>
                <span>Consume drogas</span>
              </label>
            </div>
            <label>¿Cuáles drogas?</label>
            <input
              type="text"
              name="cuales_drogas"
              data-toggle="drogas"
              value="<?php echo valueField($historia, 'cuales_drogas'); ?>"
              <?php echo disabledField($historia, 'drogas'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="toma_med" <?php echo checkedField($historia, 'toma_med'); ?>>
                <span>Toma medicamentos</span>
              </label>
            </div>
            <label>¿Para qué medicamento?</label>
            <input
              type="text"
              name="para_que_med"
              data-toggle="toma_med"
              value="<?php echo valueField($historia, 'para_que_med'); ?>"
              <?php echo disabledField($historia, 'toma_med'); ?>
            >
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="embarazo" <?php echo checkedField($historia, 'embarazo'); ?>>
                <span>Embarazo</span>
              </label>
            </div>
            <label>Mes de embarazo</label>
            <input
              type="text"
              name="mes_embarazo"
              data-toggle="embarazo"
              value="<?php echo valueField($historia, 'mes_embarazo'); ?>"
              <?php echo disabledField($historia, 'embarazo'); ?>
            >
          </div>
        </div>
      </fieldset>

      <fieldset class="wf-group">
        <legend>Hábitos</legend>

        <div class="wf-grid">
          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="fuma" <?php echo checkedField($historia, 'fuma'); ?>>
                <span>Fuma</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="toma" <?php echo checkedField($historia, 'toma'); ?>>
                <span>Toma alcohol</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="farmaco_dep" <?php echo checkedField($historia, 'farmaco_dep'); ?>>
                <span>Fármaco dependencia</span>
              </label>
            </div>
          </div>

          <div class="wf-item wf-span-2">
            <label>¿Cuáles hábitos?</label>
            <input type="text" name="cuales_habitos" value="<?php echo valueField($historia, 'cuales_habitos'); ?>">
          </div>
        </div>
      </fieldset>

      <fieldset class="wf-group">
        <legend>Información general</legend>

        <div class="wf-grid">
          <div class="wf-item wf-span-2">
            <label>Motivo de visita</label>
            <textarea name="motivo_visita" rows="3"><?php echo valueField($historia, 'motivo_visita'); ?></textarea>
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="primera_vez" <?php echo checkedField($historia, 'primera_vez'); ?>>
                <span>Primera vez</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <label>Última visita</label>
            <input type="date" name="ultima_visita" value="<?php echo valueField($historia, 'ultima_visita'); ?>">
          </div>

          <div class="wf-item">
            <div class="wf-checkline">
              <label class="wf-check">
                <input type="checkbox" name="dolor_boca" <?php echo checkedField($historia, 'dolor_boca'); ?>>
                <span>Dolor en boca</span>
              </label>
            </div>
          </div>

          <div class="wf-item">
            <label>Tipo</label>
            <input type="text" name="tipo" value="<?php echo valueField($historia, 'tipo'); ?>">
          </div>

          <div class="wf-item">
            <label>Tipo de dientes</label>
            <input type="text" name="tipo_dientes" value="<?php echo valueField($historia, 'tipo_dientes'); ?>">
          </div>
        </div>
      </fieldset>

      <fieldset class="wf-group">
        <legend>Signos vitales y diagnóstico</legend>

        <div class="wf-grid">
          <div class="wf-item">
            <label>TA</label>
            <input type="text" name="ta" value="<?php echo valueField($historia, 'ta'); ?>">
          </div>

          <div class="wf-item">
            <label>Pulso (lpm)</label>
            <input type="text" name="pulso" value="<?php echo valueField($historia, 'pulso'); ?>">
          </div>

          <div class="wf-item">
            <label>Oximetría (%)</label>
            <input type="text" name="oximetria" value="<?php echo valueField($historia, 'oximetria'); ?>">
          </div>

          <div class="wf-item">
            <label>Glucosa</label>
            <input type="text" name="glucosa" value="<?php echo valueField($historia, 'glucosa'); ?>">
          </div>

          <div class="wf-item wf-span-2">
            <label>Diagnóstico</label>
            <textarea name="diagnostico" rows="5"><?php echo valueField($historia, 'diagnostico'); ?></textarea>
          </div>
        </div>
      </fieldset>

      <div class="wf-actions">
        <button class="btn" type="submit">Guardar historia clínica</button>
      </div>
    </div>
  </form>
</section>


<section class="panel panel-winforms">
  <div class="toolbar">
    <h2>Tratamientos realizados</h2>
    <span class="muted">Los tratamientos marcados como realizados desde el plan del paciente aparecen aquí.</span>
  </div>

  <?php if (empty($tratamientosRealizados)): ?>
    <p class="muted">Aún no hay tratamientos realizados registrados en la historia clínica.</p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tratamiento</th>
            <th>Detalle</th>
            <th>Costo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tratamientosRealizados as $tratamientoRealizado): ?>
            <tr>
              <td>
                <?php
                  $fechaRealizacion = $tratamientoRealizado['fecha_realizacion'] ?? '';
                  echo htmlspecialchars($fechaRealizacion !== '' ? date('Y-m-d H:i', strtotime($fechaRealizacion)) : '');
                ?>
              </td>
              <td><?php echo htmlspecialchars($tratamientoRealizado['tratamiento_nombre'] ?? ''); ?></td>
              <td>
                Cantidad: <?php echo (int)($tratamientoRealizado['cantidad'] ?? 0); ?>
                <?php if (!empty($tratamientoRealizado['notas'])): ?>
                  <br>Notas: <?php echo htmlspecialchars($tratamientoRealizado['notas']); ?>
                <?php endif; ?>
              </td>
              <td>
                Unitario: <?php echo htmlspecialchars(pmFormatoMoneda((float)($tratamientoRealizado['precio_unitario'] ?? 0))); ?>
                <br>
                Total: <?php echo htmlspecialchars(pmFormatoMoneda((float)($tratamientoRealizado['subtotal'] ?? 0))); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const toggledFields = document.querySelectorAll('[data-toggle]');

  function updateFieldState(input) {
    const checkboxName = input.getAttribute('data-toggle');
    const checkbox = document.querySelector('input[type="checkbox"][name="' + checkboxName + '"]');
    if (!checkbox) return;

    input.disabled = !checkbox.checked;
    if (!checkbox.checked) {
      input.value = '';
    }
  }

  toggledFields.forEach(function (input) {
    updateFieldState(input);
    const checkboxName = input.getAttribute('data-toggle');
    const checkbox = document.querySelector('input[type="checkbox"][name="' + checkboxName + '"]');

    if (checkbox) {
      checkbox.addEventListener('change', function () {
        updateFieldState(input);
      });
    }
  });
});
</script>

<?php include '_layout_bottom.php'; ?>