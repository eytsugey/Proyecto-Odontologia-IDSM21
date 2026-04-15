<?php
require_once __DIR__ . '/app.php';

function googleBaseUrl(): string {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . appBasePath();
}

define('GOOGLE_CLIENT_ID', 'AQUI_TU_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'AQUI_TU_CLIENT_SECRET');
define('GOOGLE_CALENDAR_ID', 'AQUI_TU_CALENDAR_ID');

define('GOOGLE_REDIRECT_URI', googleBaseUrl() . '/public/google_callback.php');

define('GOOGLE_SCOPES', [
    'https://www.googleapis.com/auth/calendar.events',
    'https://www.googleapis.com/auth/calendar.calendarlist.readonly'
]);