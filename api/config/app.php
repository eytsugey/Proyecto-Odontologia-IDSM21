<?php
if (!defined('APP_BASE_PATH')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = '';

    if (preg_match('#^(.*?/public)(?:/.*)?$#', $scriptName, $matches)) {
        $basePath = preg_replace('#/public$#', '', $matches[1]);
    } elseif (preg_match('#^(.*?/api)(?:/.*)?$#', $scriptName, $matches)) {
        $basePath = preg_replace('#/api$#', '', $matches[1]);
    }

    define('APP_BASE_PATH', rtrim($basePath, '/'));
}

function appBasePath(): string {
    return APP_BASE_PATH;
}

function appUrl(string $path = ''): string {
    $path = ltrim($path, '/');
    $base = appBasePath();
    return ($base !== '' ? $base : '') . ($path !== '' ? '/' . $path : '');
}

function appPublicUrl(string $path = ''): string {
    return appUrl('public' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

function appApiUrl(string $path = ''): string {
    return appUrl('api' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

function redirectTo(string $path): never {
    header('Location: ' . appUrl($path));
    exit;
}

function redirectPublic(string $path): never {
    header('Location: ' . appPublicUrl($path));
    exit;
}

function redirectApi(string $path): never {
    header('Location: ' . appApiUrl($path));
    exit;
}

function redirectByRole(?string $role): never {
    $role = strtolower((string)$role);

    switch ($role) {
        case 'secretaria':
            redirectPublic('secretaria.php');
        case 'doctor':
            redirectPublic('doctor_agenda.php');
        case 'admin':
        case 'administrador':
            redirectPublic('admin.php');
        default:
            redirectPublic('login.php');
    }
}
