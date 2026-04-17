<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/helpers/professional_modules.php';

requireLogin(['admin', 'secretaria']);
ensureProfessionalModules($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

$pacienteId = (int)($_POST['paciente_id'] ?? 0);
$citaId = (int)($_POST['cita_id'] ?? 0);
$tratamientoId = (int)($_POST['tratamiento_id'] ?? 0);
$concepto = trim($_POST['concepto'] ?? '');
$monto = (float)($_POST['monto'] ?? 0);
$metodo = trim($_POST['metodo_pago'] ?? 'efectivo');
$estado = trim($_POST['estado'] ?? 'pagado');
$referencia = trim($_POST['referencia'] ?? '');
$fechaPago = $_POST['fecha_pago'] ?? date('Y-m-d');
$observaciones = trim($_POST['observaciones'] ?? '');

if ($citaId > 0) {
    $stmt = $pdo->prepare('SELECT c.id, c.paciente_id, p.nombre AS paciente FROM citas c JOIN pacientes p ON p.id = c.paciente_id WHERE c.id = ? LIMIT 1');
    $stmt->execute([$citaId]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cita) {
        redirectPublic('pagos.php?error=cita');
    }
    $pacienteId = (int)$cita['paciente_id'];

    if ($tratamientoId <= 0) {
        $stmt = $pdo->prepare('SELECT tratamiento_id FROM cita_tratamientos WHERE cita_id = ? ORDER BY id ASC LIMIT 1');
        $stmt->execute([$citaId]);
        $tratamientoId = (int)($stmt->fetchColumn() ?: 0);
    }

    $resumen = pmResumenCuentaCita($pdo, $citaId);
    if ($monto <= 0) {
        $monto = (float)$resumen['saldo'];
    }
    if ($concepto === '') {
        $concepto = 'Pago cita #' . $citaId;
    }
}

if ($concepto === '' || $monto <= 0 || $fechaPago === '') {
    redirectPublic('pagos.php?error=datos');
}

$metodosPermitidos = ['efectivo', 'tarjeta', 'transferencia', 'otro'];
$estadosPermitidos = ['pagado', 'pendiente', 'parcial'];

if (!in_array($metodo, $metodosPermitidos, true)) {
    $metodo = 'otro';
}
if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'pagado';
}

$stmt = $pdo->prepare('INSERT INTO pagos (paciente_id, cita_id, tratamiento_id, concepto, monto, metodo_pago, estado, referencia, fecha_pago, observaciones, registrado_por) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([
    $pacienteId > 0 ? $pacienteId : null,
    $citaId > 0 ? $citaId : null,
    $tratamientoId > 0 ? $tratamientoId : null,
    $concepto,
    $monto,
    $metodo,
    $estado,
    $referencia !== '' ? $referencia : null,
    $fechaPago,
    $observaciones !== '' ? $observaciones : null,
    (int)($_SESSION['usuario_id'] ?? 0) ?: null,
]);

redirectPublic('pagos.php?ok=1' . ($citaId > 0 ? '&cita_id=' . $citaId : ''));
