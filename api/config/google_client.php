<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/google_calendar.php';

function googleTokenPath(): string
{
    return __DIR__ . '/google_token.json';
}

function loadGoogleToken(): ?array
{
    $path = googleTokenPath();

    if (!file_exists($path)) {
        return null;
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    return is_array($data) ? $data : null;
}

function saveGoogleToken(array $token): void
{
    $path = googleTokenPath();

    $existing = loadGoogleToken();

    if (
        isset($existing['refresh_token']) &&
        !isset($token['refresh_token'])
    ) {
        $token['refresh_token'] = $existing['refresh_token'];
    }

    file_put_contents(
        $path,
        json_encode($token, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

function buildGoogleClient(): Google_Client
{
    $client = new Google_Client();

    $client->setClientId(GOOGLE_CLIENT_ID);
    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
    $client->setScopes(GOOGLE_SCOPES);

    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return $client;
}