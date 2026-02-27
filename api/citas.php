<?php
session_start();

require_once __DIR__ . "/config/database.php"; // $pdo
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER["REQUEST_METHOD"];

try {

  // ============================
  // ✅ 0) SOLICITAR CITA (PÚBLICO)
  // ============================
  if ($method === "POST") {

    // Form público (no JSON)
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $motivo = trim($_POST["motivo"] ?? "");
    $fecha = trim($_POST["fecha_preferida"] ?? "");
    $hora = trim($_POST["hora_preferida"] ?? "");

    if ($nombre === "" || $telefono === "" || $motivo === "") {
      http_response_code(400);
      echo json_encode(["error" => "Faltan campos obligatorios (nombre, telefono, motivo)."]);
      exit;
    }

    // 1) Crear paciente (mínimo)
    $stmtP = $pdo->prepare("INSERT INTO pacientes (nombre, apellido, telefono) VALUES (?, ?, ?)");
    $stmtP->execute([$nombre, $apellido, $telefono]);
    $id_paciente = (int)$pdo->lastInsertId();

    // 2) Crear cita como solicitada
    $stmtC = $pdo->prepare("INSERT INTO citas (id_paciente, fecha, hora, motivo, estado) VALUES (?, ?, ?, ?, 'solicitada')");
    $stmtC->execute([
      $id_paciente,
      ($fecha === "" ? null : $fecha),
      ($hora === "" ? null : $hora),
      $motivo
    ]);

    echo json_encode(["success" => true, "message" => "Solicitud enviada. Un secretario la confirmará."]);
    exit;
  }

  // 🔐 De aquí en adelante: SOLO SECRETARIO
  require_once __DIR__ . "/middleware/auth.php";
  validarSesion("secretario");

  // ============================
  // 1) OBTENER CITAS SOLICITADAS
  // ============================
  if ($method === "GET") {

    $stmt = $pdo->prepare(
      "SELECT c.id_cita, p.nombre, p.apellido, c.fecha, c.hora
       FROM citas c
       JOIN pacientes p ON c.id_paciente = p.id_paciente
       WHERE c.estado = 'solicitada'"
    );

    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
  }

  // ============================
  // 2) CONFIRMAR CITA
  // ============================
  if ($method === "PUT") {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare("UPDATE citas SET estado = 'confirmada' WHERE id_cita = ?");
    $stmt->execute([$data["id_cita"] ?? 0]);

    echo json_encode(["success" => true]);
    exit;
  }

  // ============================
  // 3) CANCELAR CITA
  // ============================
  if ($method === "DELETE") {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $pdo->prepare("UPDATE citas SET estado = 'cancelada' WHERE id_cita = ?");
    $stmt->execute([$data["id_cita"] ?? 0]);

    echo json_encode(["success" => true]);
    exit;
  }

  http_response_code(405);
  echo json_encode(["error" => "Método no permitido"]);

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error del servidor", "detail" => $e->getMessage()]);
}