<?php
$host = "localhost";
$db   = "clinica_dental";
$user = "root";
$pass = "";

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);