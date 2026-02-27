<?php
session_start();
require_once __DIR__ . "/../api/config/database.php";

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
  header("Location: login.php?error=1");
  exit;
}

$sql = "SELECT id_usuario, nombre, email, password, rol, activo
        FROM usuarios
        WHERE email = ?
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u) {
  header("Location: login.php?error=1");
  exit;
}

if (isset($u["activo"]) && (int)$u["activo"] === 0) {
  header("Location: login.php?error=blocked");
  exit;
}

// Password en texto plano (por ahora)
if ($password !== $u["password"]) {
  header("Location: login.php?error=1");
  exit;
}

$_SESSION["user"] = [
  "id" => (int)$u["id_usuario"],
  "nombre" => $u["nombre"],
  "email" => $u["email"],
  "rol" => $u["rol"],
];

$rol = strtolower((string)$u["rol"]);

// ✅ Aquí mandas al admin.php
if ($rol === "administrador" || $rol === "admin") {
  header("Location: admin.php");
  exit;
}

// Si no es admin, lo mandas a index o a otra vista
header("Location: index.html");
exit;