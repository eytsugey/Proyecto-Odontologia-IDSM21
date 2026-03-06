<?php
session_start();

$_SESSION = [];

// Destruir sesión
session_destroy();

// Redirigir al login
header("Location: login.php");
exit;
