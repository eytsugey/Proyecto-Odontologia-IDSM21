<?php
session_start();
session_destroy();
header('Location: /proyecto_odontologia_funcional/public/login.php');
exit;
?>
