<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/helpers/professional_modules.php';

requireLogin(['admin']);
ensureProfessionalModules($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? 'create';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método no permitido';
    exit;
}

if ($action === 'create') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $rol = normalizarRol($_POST['rol'] ?? 'secretaria');
    $password = (string)($_POST['password'] ?? '');

    if ($nombre === '' || $correo === '' || $password === '') {
        redirectPublic('usuarios.php?error=datos');
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        redirectPublic('usuarios.php?error=correo');
    }

    if (!in_array($rol, ['admin', 'doctor', 'secretaria'], true)) {
        $rol = 'secretaria';
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
    $stmt->execute([$correo]);
    if ($stmt->fetch()) {
        redirectPublic('usuarios.php?error=duplicado');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, correo, password, rol, activo) VALUES (?,?,?,?,1)');
    $stmt->execute([$nombre, $correo, $hash, $rol]);
    redirectPublic('usuarios.php?ok=creado');
}

$userId = (int)($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    redirectPublic('usuarios.php?error=id');
}

if ($action === 'toggle') {
    if ($userId === (int)($_SESSION['usuario_id'] ?? 0)) {
        redirectPublic('usuarios.php?error=self');
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET activo = IF(COALESCE(activo,1)=1,0,1) WHERE id = ?');
    $stmt->execute([$userId]);
    redirectPublic('usuarios.php?ok=estado');
}

if ($action === 'role') {
    $rol = normalizarRol($_POST['rol'] ?? 'secretaria');
    if (!in_array($rol, ['admin', 'doctor', 'secretaria'], true)) {
        $rol = 'secretaria';
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET rol = ? WHERE id = ?');
    $stmt->execute([$rol, $userId]);
    redirectPublic('usuarios.php?ok=rol');
}

if ($action === 'reset_password') {
    $nueva = (string)($_POST['nuevo_password'] ?? '');
    if (strlen($nueva) < 4) {
        redirectPublic('usuarios.php?error=pass');
    }

    $stmt = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
    $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $userId]);
    redirectPublic('usuarios.php?ok=pass');
}

redirectPublic('usuarios.php?error=accion');
