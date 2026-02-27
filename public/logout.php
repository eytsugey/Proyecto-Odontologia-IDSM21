<?php
session_start();

// Vaciar variables de sesión
$_SESSION = [];

// Destruir sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit;