<?php
session_start();
if (!isset($_SESSION["user"])) {
  header("Location: login.html");
  exit;
}
?>

<?php
session_start();
if (!isset($_SESSION["admin_id"])) { header("Location: login.html"); exit(); }
?>
<h2>Bienvenido, <?php echo htmlspecialchars($_SESSION["admin_nombre"]); ?></h2>
<a href="logout.php">Cerrar sesión</a>
