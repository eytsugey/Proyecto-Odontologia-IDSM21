<?php
require_once __DIR__ . '/../config/twilio.php';
require_once __DIR__ . '/schema.php';

function twilioIsEnabled(): bool {
    $config = twilioConfig();
    return !empty($config['enabled'])
        && trim((string)($config['account_sid'] ?? '')) !== ''
        && trim((string)($config['auth_token'] ?? '')) !== ''
        && trim((string)($config['messaging_service_sid'] ?? '')) !== '';
}

function twilioNormalizePhone(string $phone): string {
    $config = twilioConfig();
    $defaultCountry = preg_replace('/\D+/', '', (string)($config['default_country_code'] ?? '52'));
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if ($digits === '') {
        return '';
    }

    if (str_starts_with(trim($phone), '+')) {
        return '+' . $digits;
    }

    if (strlen($digits) === 10 && $defaultCountry !== '') {
        return '+' . $defaultCountry . $digits;
    }

    if ($defaultCountry !== '' && str_starts_with($digits, $defaultCountry)) {
        return '+' . $digits;
    }

    if (strlen($digits) >= 11 && strlen($digits) <= 15) {
        return '+' . $digits;
    }

    return '';
}

function twilioFetchCita(PDO $pdo, int $citaId): ?array {
    $mode = citasDoctorMode($pdo);

    if ($mode === 'cedula') {
        $sql = "SELECT c.id, c.paciente_id, p.nombre AS paciente, p.telefono, d.nombre AS doctor,
                       c.fecha, c.hora, c.motivo_consulta, LOWER(c.estado) AS estado
                FROM citas c
                JOIN pacientes p ON p.id = c.paciente_id
                LEFT JOIN doctores d ON d.cedula = c.cedula_doctor
                WHERE c.id = ?
                LIMIT 1";
    } else {
        $sql = "SELECT c.id, c.paciente_id, p.nombre AS paciente, p.telefono,
                       CONCAT('Doctor ', c.doctor_id) AS doctor,
                       c.fecha, c.hora, c.motivo_consulta, LOWER(c.estado) AS estado
                FROM citas c
                JOIN pacientes p ON p.id = c.paciente_id
                WHERE c.id = ?
                LIMIT 1";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$citaId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function twilioFormatDate(?string $date): string {
    if (!$date) {
        return '';
    }

    try {
        return (new DateTime($date))->format('d/m/Y');
    } catch (Throwable $e) {
        return (string)$date;
    }
}

function twilioFormatTime(?string $time): string {
    $time = trim((string)$time);
    if ($time === '') {
        return '';
    }

    $short = substr($time, 0, 5);
    $timestamp = strtotime($short);
    if ($timestamp === false) {
        return $short;
    }

    return date('g:i A', $timestamp);
}

function twilioBuildCitaMessage(string $event, array $cita): string {
    $paciente = trim((string)($cita['paciente'] ?? 'Paciente'));
    $doctor = trim((string)($cita['doctor'] ?? 'Doctor'));
    $fecha = twilioFormatDate($cita['fecha'] ?? null);
    $hora = twilioFormatTime($cita['hora'] ?? null);
    $motivo = trim((string)($cita['motivo_consulta'] ?? ''));
    $motivoTexto = $motivo !== '' ? ' Motivo: ' . $motivo . '.' : '';

    return match ($event) {
        'solicitud_publica' => "Clinica dental: se recibio la solicitud de cita de {$paciente} para el {$fecha} a las {$hora}. {$doctor} revisara la agenda." . $motivoTexto,
        'creada' => "Clinica dental: cita registrada para {$paciente} el {$fecha} a las {$hora} con {$doctor}." . $motivoTexto,
        'reprogramada' => "Clinica dental: cita actualizada para {$paciente}. Nueva fecha: {$fecha} a las {$hora} con {$doctor}." . $motivoTexto,
        'confirmada' => "Clinica dental: la cita de {$paciente} fue confirmada para el {$fecha} a las {$hora} con {$doctor}." . $motivoTexto,
        'cancelada' => "Clinica dental: la cita de {$paciente} fue cancelada. Para reagendar, comunicarse con recepcion.",
        'atendida' => "Clinica dental: la cita de {$paciente} fue marcada como atendida. Gracias por la visita.",
        default => "Clinica dental: actualizacion de cita para {$paciente} el {$fecha} a las {$hora}." . $motivoTexto,
    };
}

function twilioSendSms(string $to, string $body): array {
    if (!twilioIsEnabled()) {
        return ['ok' => false, 'error' => 'Twilio no configurado'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL no disponible'];
    }

    $config = twilioConfig();
    $to = twilioNormalizePhone($to);
    if ($to === '') {
        return ['ok' => false, 'error' => 'Telefono invalido'];
    }

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode((string)$config['account_sid']) . '/Messages.json';
    $postFields = http_build_query([
        'To' => $to,
        'MessagingServiceSid' => (string)$config['messaging_service_sid'],
        'Body' => $body,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => (string)$config['account_sid'] . ':' . (string)$config['auth_token'],
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'No fue posible contactar a Twilio'];
    }

    $json = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'ok' => false,
            'error' => (string)($json['message'] ?? 'Twilio devolvio un error'),
            'response' => $json,
            'status_code' => $httpCode,
        ];
    }

    return [
        'ok' => true,
        'sid' => (string)($json['sid'] ?? ''),
        'response' => $json,
        'status_code' => $httpCode,
    ];
}

function twilioNotifyCita(PDO $pdo, int $citaId, string $event): bool {
    if (!twilioIsEnabled() || $citaId <= 0) {
        return false;
    }

    try {
        $cita = twilioFetchCita($pdo, $citaId);
        if (!$cita) {
            return false;
        }

        $telefono = trim((string)($cita['telefono'] ?? ''));
        if ($telefono === '') {
            return false;
        }

        $body = twilioBuildCitaMessage($event, $cita);
        $result = twilioSendSms($telefono, $body);

        if (empty($result['ok'])) {
            error_log('Twilio SMS no enviado para cita #' . $citaId . ': ' . (string)($result['error'] ?? 'Error desconocido'));
            return false;
        }

        return true;
    } catch (Throwable $e) {
        error_log('Twilio SMS fallo para cita #' . $citaId . ': ' . $e->getMessage());
        return false;
    }
}
