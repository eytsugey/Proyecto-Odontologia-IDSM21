<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/helpers/professional_modules.php';

requireLogin(['admin', 'secretaria', 'doctor']);
ensureProfessionalModules($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

$action = $_POST['action'] ?? 'create';

if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio_base'] ?? 0);

    if ($nombre === '' || $precio <= 0) {
        redirectPublic('tratamientos.php?error=datos');
    }

    $stmt = $pdo->prepare('INSERT INTO tratamientos_catalogo (nombre, descripcion, precio_base, activo) VALUES (?,?,?,1)');
    $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null, $precio]);
    redirectPublic('tratamientos.php?ok=1');
}

if ($action === 'update') {
    $id = (int)($_POST['tratamiento_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio_base'] ?? 0);

    if ($id <= 0 || $nombre === '' || $precio <= 0) {
        redirectPublic('tratamientos.php?error=datos');
    }

    $stmt = $pdo->prepare('UPDATE tratamientos_catalogo SET nombre = ?, descripcion = ?, precio_base = ? WHERE id = ?');
    $stmt->execute([$nombre, $descripcion !== '' ? $descripcion : null, $precio, $id]);
    redirectPublic('tratamientos.php?ok=editado');
}

if ($action === 'toggle') {
    $id = (int)($_POST['tratamiento_id'] ?? 0);
    if ($id <= 0) {
        redirectPublic('tratamientos.php?error=id');
    }
    $stmt = $pdo->prepare('UPDATE tratamientos_catalogo SET activo = IF(COALESCE(activo,1)=1,0,1) WHERE id = ?');
    $stmt->execute([$id]);
    redirectPublic('tratamientos.php?ok=estado');
}

redirectPublic('tratamientos.php?error=accion');
