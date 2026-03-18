<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function requireLogin(array $roles = []): void {
    if (empty($_SESSION['usuario_id'])) {
        header('Location: /Proyecto-Odontologia-IDSM21/public/login.php');
        exit;
    }
    if ($roles && !in_array($_SESSION['rol'], $roles, true)) {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
}
?>
