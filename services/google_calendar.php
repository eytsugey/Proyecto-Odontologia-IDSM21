<?php
require __DIR__ . '/../../vendor/autoload.php';

function getGoogleClient(){

    $client = new Google_Client();
    $client->setAuthConfig(__DIR__ . '/../config/google_credentials.json');
    $client->addScope(Google_Service_Calendar::CALENDAR);

    $client->setRedirectUri('http://localhost/Proyecto-Odontologia-IDSM21/public/oauth_callback.php');

    $client->setAccessType('offline');
    $client->setPrompt('consent');

    return $client;
}

function crearEventoGoogle($paciente, $fecha, $hora, $motivo){

    $client = getGoogleClient();

    $tokenPath = __DIR__ . '/../config/token.json';
    $client->setAccessToken(json_decode(file_get_contents($tokenPath), true));

    $service = new Google_Service_Calendar($client);

    $fechaFin = date("Y-m-d\TH:i:s", strtotime("$fecha $hora +30 minutes"));

    $event = new Google_Service_Calendar_Event([
        'summary' => 'Cita: ' . $paciente,
        'description' => $motivo,
        'start' => [
            'dateTime' => $fecha . 'T' . $hora,
            'timeZone' => 'America/Mexico_City',
        ],
        'end' => [
            'dateTime' => $fechaFin,
            'timeZone' => 'America/Mexico_City',
        ],
    ]);

    return $service->events->insert('primary', $event);
}
