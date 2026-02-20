<?php
session_start();

$pdo = new PDO(
    "mysql:host=localhost;dbname=odontologia_db;charset=utf8",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);
?>