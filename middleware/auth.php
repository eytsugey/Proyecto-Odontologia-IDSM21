<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function normalizarRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    return match ($rol) {
        'administrador' => 'admin',
        default => $rol,
    };
}

function usuarioTieneRol(string $rolUsuario, array $rolesPermitidos): bool {
    $rolUsuario = normalizarRol($rolUsuario);
    $rolesPermitidos = array_map('normalizarRol', $rolesPermitidos);

    if (in_array($rolUsuario, $rolesPermitidos, true)) {
        return true;
    }

    return $rolUsuario === 'admin';
}

function requireLogin(array $roles = []): void {
    if (empty($_SESSION['usuario_id'])) {
        redirectPublic('login.php');
    }

    $_SESSION['rol'] = normalizarRol($_SESSION['rol'] ?? '');

    if ($roles && !usuarioTieneRol($_SESSION['rol'], $roles)) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}
?>
