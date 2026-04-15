<?php
session_start();
require_once __DIR__ . '/../config/app.php';
session_destroy();
redirectPublic('login.php');
?>
