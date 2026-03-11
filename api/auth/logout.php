<?php
session_start();
session_destroy();
header('Location: /Proyecto-Odontologia-IDSM21/public/login.php');
exit;
?>
