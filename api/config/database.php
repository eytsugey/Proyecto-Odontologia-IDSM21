<?php
require_once __DIR__ . '/app.php';

$host = 'localhost';
$user = 'root';
$pass = '';
$port = '3306';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$dbCandidates = array_unique(array_filter([
    getenv('ODONTO_DB') ?: null,
    'odontologia_db',
    'odontologia',
]));

$lastError = null;
$pdo = null;

foreach ($dbCandidates as $db) {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, $options);
        break;
    } catch (PDOException $e) {
        $lastError = $e;
    }
}

if (!$pdo) {
    die('Error de conexión: ' . ($lastError ? $lastError->getMessage() : 'No fue posible conectar a la base de datos.'));
}
?>
