<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../middleware/auth.php';

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if ($correo === '' || $password === '') {
    redirectPublic('login.php?error=1');
}

$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE correo = ? LIMIT 1');
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$loginValido = false;

if ($user && (int)($user['activo'] ?? 1) === 1) {
    $hashGuardado = (string)($user['password'] ?? '');

    if ($hashGuardado !== '' && password_verify($password, $hashGuardado)) {
        $loginValido = true;
    } elseif (hash_equals($hashGuardado, $password)) {
        $loginValido = true;

        try {
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?');
            $upd->execute([$nuevoHash, (int)$user['id']]);
            $user['password'] = $nuevoHash;
        } catch (Throwable $e) {
        }
    }
}

if ($loginValido && $user) {
    $_SESSION['usuario_id'] = (int)$user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = normalizarRol($user['rol'] ?? '');
    redirectByRole($_SESSION['rol']);
}

redirectPublic('login.php?error=1');
?>
