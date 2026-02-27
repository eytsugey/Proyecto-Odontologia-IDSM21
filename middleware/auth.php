<?php
session_start();

function validarSesion($rolRequerido) {

    if (!isset($_SESSION['usuario'])) {
        http_response_code(401);
        echo json_encode(["error" => "No autenticado"]);
        exit;
    }

    if ($_SESSION['usuario']['rol'] !== $rolRequerido) {
        http_response_code(403);
        echo json_encode(["error" => "Acceso denegado"]);
        exit;
    }
}