<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/helpers/professional_modules.php';

requireLogin(['admin', 'doctor', 'secretaria']);
ensureProfessionalModules($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

$action = $_POST['action'] ?? 'crear_plan';
$pacienteId = (int)($_POST['paciente_id'] ?? 0);
if ($pacienteId <= 0) {
    redirectPublic('plan_tratamiento.php?error=paciente');
}

if ($action === 'crear_plan') {
    $titulo = trim($_POST['titulo'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $objetivo = trim($_POST['objetivo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'propuesto');
    $citaId = (int)($_POST['cita_id'] ?? 0);

    if ($titulo === '') {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&error=plan');
    }

    $stmt = $pdo->prepare('INSERT INTO planes_tratamiento (paciente_id, cita_id, titulo, diagnostico, objetivo, estado) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$pacienteId, $citaId > 0 ? $citaId : null, $titulo, $diagnostico !== '' ? $diagnostico : null, $objetivo !== '' ? $objetivo : null, $estado !== '' ? $estado : 'propuesto']);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&ok=plan');
}

$planId = (int)($_POST['plan_id'] ?? 0);

if ($action === 'cotizar_odontograma') {
    $diente = trim((string)($_POST['diente'] ?? ''));
    $estadoOdontograma = strtolower(trim((string)($_POST['estado_odontograma'] ?? '')));
    $descripcionOdontograma = trim((string)($_POST['descripcion_odontograma'] ?? ''));

    if ($diente === '' || $estadoOdontograma === '' || $estadoOdontograma === 'sano') {
        redirectPublic('odontograma.php?paciente_id=' . $pacienteId . '&error=cotizacion');
    }

    $aliases = match ($estadoOdontograma) {
        'caries' => ['Resina', 'Restauración', 'Obturación'],
        'restaurado' => ['Revisión de restauración', 'Resina', 'Restauración'],
        'extraido' => ['Prótesis', 'Implante', 'Extracción'],
        'fracturado' => ['Corona', 'Reconstrucción', 'Resina'],
        'endodoncia' => ['Endodoncia'],
        default => [],
    };

    $tratamiento = pmBuscarTratamientoPorAlias($pdo, $aliases);
    if (!$tratamiento) {
        redirectPublic('odontograma.php?paciente_id=' . $pacienteId . '&error=sin_tratamiento_catalogo');
    }

    $planId = pmObtenerOCrearPlanOdontograma($pdo, $pacienteId);
    $notas = 'Cotización sugerida desde odontograma. Diente ' . $diente . '. Estado: ' . $estadoOdontograma . '.';
    if ($descripcionOdontograma !== '') {
        $notas .= ' Notas: ' . $descripcionOdontograma;
    }

    pmAgregarItemPlanSiNoExiste($pdo, $planId, (int)$tratamiento['id'], 1, 'pendiente', $notas);
    redirectPublic('odontograma.php?paciente_id=' . $pacienteId . '&cotizado=1&plan_id=' . $planId);
}

if ($planId <= 0) {
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&error=plan');
}

if ($action === 'agregar_fase') {
    $nombre = trim($_POST['nombre_fase'] ?? '');
    $descripcion = trim($_POST['descripcion_fase'] ?? '');
    $orden = max(1, (int)($_POST['orden_fase'] ?? 1));
    $estado = trim($_POST['estado_fase'] ?? 'pendiente');
    $fechaObjetivo = $_POST['fecha_objetivo'] ?? '';

    if ($nombre === '') {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=fase');
    }

    $stmt = $pdo->prepare('INSERT INTO plan_tratamiento_fases (plan_id, nombre, descripcion, orden, estado, fecha_objetivo) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$planId, $nombre, $descripcion !== '' ? $descripcion : null, $orden, $estado !== '' ? $estado : 'pendiente', $fechaObjetivo !== '' ? $fechaObjetivo : null]);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=fase');
}

if ($action === 'actualizar_fase') {
    $faseId = (int)($_POST['fase_id'] ?? 0);
    $nombre = trim($_POST['nombre_fase'] ?? '');
    $descripcion = trim($_POST['descripcion_fase'] ?? '');
    $orden = max(1, (int)($_POST['orden_fase'] ?? 1));
    $estado = trim($_POST['estado_fase'] ?? 'pendiente');
    $fechaObjetivo = $_POST['fecha_objetivo'] ?? '';

    if ($faseId <= 0 || $nombre === '') {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=fase');
    }

    $stmt = $pdo->prepare('UPDATE plan_tratamiento_fases SET nombre = ?, descripcion = ?, orden = ?, estado = ?, fecha_objetivo = ? WHERE id = ? AND plan_id = ?');
    $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null, $orden, $estado !== '' ? $estado : 'pendiente', $fechaObjetivo !== '' ? $fechaObjetivo : null, $faseId, $planId]);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=fase_editada');
}

if ($action === 'eliminar_fase') {
    $faseId = (int)($_POST['fase_id'] ?? 0);
    if ($faseId <= 0) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=fase');
    }

    $stmt = $pdo->prepare('UPDATE plan_tratamiento_items SET fase_id = NULL WHERE fase_id = ? AND plan_id = ?');
    $stmt->execute([$faseId, $planId]);
    $stmt = $pdo->prepare('DELETE FROM plan_tratamiento_fases WHERE id = ? AND plan_id = ?');
    $stmt->execute([$faseId, $planId]);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=fase_eliminada');
}

if ($action === 'agregar_item') {
    $faseId = (int)($_POST['fase_id'] ?? 0);
    $tratamientoId = (int)($_POST['tratamiento_id'] ?? 0);
    $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
    $estado = trim($_POST['estado_item'] ?? 'pendiente');
    $notas = trim($_POST['notas_item'] ?? '');

    if ($tratamientoId <= 0) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=item');
    }

    $stmt = $pdo->prepare('SELECT precio_base FROM tratamientos_catalogo WHERE id = ? LIMIT 1');
    $stmt->execute([$tratamientoId]);
    $precio = $stmt->fetchColumn();
    if ($precio === false) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=item');
    }

    $subtotal = ((float)$precio) * $cantidad;
    $stmt = $pdo->prepare('INSERT INTO plan_tratamiento_items (plan_id, fase_id, tratamiento_id, cantidad, precio_unitario, subtotal, estado, notas) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$planId, $faseId > 0 ? $faseId : null, $tratamientoId, $cantidad, $precio, $subtotal, $estado !== '' ? $estado : 'pendiente', $notas !== '' ? $notas : null]);
    pmRecalcularPlanTotal($pdo, $planId);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=item');
}

if ($action === 'actualizar_item') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $faseId = (int)($_POST['fase_id'] ?? 0);
    $cantidad = max(1, (int)($_POST['cantidad'] ?? 1));
    $estado = trim($_POST['estado_item'] ?? 'pendiente');
    $notas = trim($_POST['notas_item'] ?? '');

    if ($itemId <= 0) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=item');
    }

    $stmt = $pdo->prepare('SELECT precio_unitario FROM plan_tratamiento_items WHERE id = ? AND plan_id = ? LIMIT 1');
    $stmt->execute([$itemId, $planId]);
    $precio = $stmt->fetchColumn();
    if ($precio === false) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=item');
    }

    $subtotal = ((float)$precio) * $cantidad;
    $stmt = $pdo->prepare('UPDATE plan_tratamiento_items SET fase_id = ?, cantidad = ?, subtotal = ?, estado = ?, notas = ? WHERE id = ? AND plan_id = ?');
    $stmt->execute([$faseId > 0 ? $faseId : null, $cantidad, $subtotal, $estado !== '' ? $estado : 'pendiente', $notas !== '' ? $notas : null, $itemId, $planId]);
    pmRecalcularPlanTotal($pdo, $planId);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=item_editado');
}

if ($action === 'eliminar_item') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    if ($itemId <= 0) {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=item');
    }

    $stmt = $pdo->prepare('DELETE FROM plan_tratamiento_items WHERE id = ? AND plan_id = ?');
    $stmt->execute([$itemId, $planId]);
    pmRecalcularPlanTotal($pdo, $planId);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=item_eliminado');
}

if ($action === 'actualizar_plan') {
    $titulo = trim($_POST['titulo'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $objetivo = trim($_POST['objetivo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'propuesto');

    if ($titulo === '') {
        redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&error=plan');
    }

    $stmt = $pdo->prepare('UPDATE planes_tratamiento SET titulo = ?, diagnostico = ?, objetivo = ?, estado = ? WHERE id = ? AND paciente_id = ?');
    $stmt->execute([$titulo, $diagnostico !== '' ? $diagnostico : null, $objetivo !== '' ? $objetivo : null, $estado !== '' ? $estado : 'propuesto', $planId, $pacienteId]);
    redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&plan_id=' . $planId . '&ok=plan_editado');
}

redirectPublic('plan_tratamiento.php?paciente_id=' . $pacienteId . '&error=accion');
