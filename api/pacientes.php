<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $edad = ($_POST['edad'] ?? '') !== '' ? (int)$_POST['edad'] : null;
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
    $telefono = trim($_POST['telefono'] ?? '');

    if ($nombre === '' || $telefono === '' || $sexo === '') {
        header('Location: /Proyecto-Odontologia-IDSM21/public/pacientes.php?error=1');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES (?,?,?,?,?)');
    $stmt->execute([$nombre, $sexo, $edad, $fecha_nacimiento, $telefono]);

    header('Location: /Proyecto-Odontologia-IDSM21/public/pacientes.php?ok=1');
    exit;
}

http_response_code(405);
echo 'Método no permitido';