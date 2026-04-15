<?php
require_once __DIR__ . '/../api/services/google_calendar.php';

$client = getGoogleClient();

if (!isset($_GET['code'])) {
    header('Location: ' . $client->createAuthUrl());
    exit;
} else {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    file_put_contents(__DIR__ . '/../api/config/token.json', json_encode($token));
    echo "✅ Autorizado correctamente";
}