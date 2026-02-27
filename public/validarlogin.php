<?php
session_start();

$conn = new mysqli("127.0.0.1", "root", "", "odontologia_db", 3307);

if ($conn->connect_error) {
    die("Error de conexión");
}

$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM usuarios 
        WHERE email=? 
        AND password=? 
        AND rol='administrador' 
        AND activo=1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $_SESSION['admin'] = $email;
    header("Location: admin.php");
    exit();
} else {
    header("Location: login.php?error=1");
    exit();
}