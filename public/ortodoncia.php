<?php
require_once __DIR__ . '/../api/config/app.php';
$pacienteId = (int)($_GET['paciente_id'] ?? 0);
$destino = 'plan_tratamiento.php';
if ($pacienteId > 0) {
    $destino .= '?paciente_id=' . $pacienteId;
}
redirectPublic($destino);
