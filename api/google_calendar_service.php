<?php
require_once __DIR__ . '/config/google_client.php';

function googleCalendarTimezone(): string
{
    return 'America/Chihuahua';
}

function googleCalendarDefaultDurationMinutes(): int
{
    return 60;
}

function getGoogleCalendarService(): Google_Service_Calendar
{
    $client = buildGoogleClient();
    $token = loadGoogleToken();

    if (!$token) {
        throw new Exception('No existe token de Google guardado. Primero inicia sesión con Google.');
    }

    $client->setAccessToken($token);

    if ($client->isAccessTokenExpired()) {
        $refreshToken = $client->getRefreshToken();

        if (!$refreshToken && isset($token['refresh_token'])) {
            $refreshToken = $token['refresh_token'];
        }

        if (!$refreshToken) {
            throw new Exception('El token de Google expiró y no hay refresh token. Vuelve a iniciar sesión.');
        }

        $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (isset($newToken['error'])) {
            throw new Exception('No se pudo refrescar el token de Google: ' . $newToken['error']);
        }

        saveGoogleToken($client->getAccessToken());
    }

    return new Google_Service_Calendar($client);
}

function buildGoogleCalendarEventData(array $cita): Google_Service_Calendar_Event
{
    $timezone = googleCalendarTimezone();
    $duration = googleCalendarDefaultDurationMinutes();

    $fecha = $cita['fecha'] ?? null;
    $hora = $cita['hora'] ?? null;

    if (!$fecha || !$hora) {
        throw new Exception('La cita no tiene fecha u hora válidas.');
    }

    $inicio = new DateTime($fecha . ' ' . $hora, new DateTimeZone($timezone));
    $fin = clone $inicio;
    $fin->modify("+{$duration} minutes");

    $paciente = trim((string)($cita['paciente_nombre'] ?? 'Paciente sin nombre'));
    $telefono = trim((string)($cita['paciente_telefono'] ?? ''));
    $motivo = trim((string)($cita['motivo_consulta'] ?? 'Sin motivo especificado'));
    $doctor = trim((string)($cita['doctor_nombre'] ?? 'Doctor no asignado'));
    $estado = trim((string)($cita['estado'] ?? 'pendiente'));
    $citaId = $cita['id'] ?? '';

    $summary = "Cita dental - {$paciente}";
    $description = "ID cita: {$citaId}\n";
    $description .= "Paciente: {$paciente}\n";
    $description .= "Teléfono: " . ($telefono !== '' ? $telefono : 'No disponible') . "\n";
    $description .= "Doctor: {$doctor}\n";
    $description .= "Motivo: {$motivo}\n";
    $description .= "Estado: {$estado}";

    return new Google_Service_Calendar_Event([
        'summary' => $summary,
        'description' => $description,
        'start' => [
            'dateTime' => $inicio->format(DateTime::ATOM),
            'timeZone' => $timezone,
        ],
        'end' => [
            'dateTime' => $fin->format(DateTime::ATOM),
            'timeZone' => $timezone,
        ],
    ]);
}

function googleCalendarCreateEvent(array $cita): Google_Service_Calendar_Event
{
    $service = getGoogleCalendarService();
    $event = buildGoogleCalendarEventData($cita);

    return $service->events->insert(GOOGLE_CALENDAR_ID, $event);
}

function googleCalendarUpdateEvent(string $googleEventId, array $cita): Google_Service_Calendar_Event
{
    if (trim($googleEventId) === '') {
        throw new Exception('No se proporcionó google_event_id para actualizar.');
    }

    $service = getGoogleCalendarService();
    $event = buildGoogleCalendarEventData($cita);

    return $service->events->update(GOOGLE_CALENDAR_ID, $googleEventId, $event);
}

function googleCalendarDeleteEvent(string $googleEventId): bool
{
    if (trim($googleEventId) === '') {
        return false;
    }

    $service = getGoogleCalendarService();
    $service->events->delete(GOOGLE_CALENDAR_ID, $googleEventId);

    return true;
}
