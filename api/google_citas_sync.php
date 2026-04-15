<?php
require_once __DIR__ . '/google_calendar_service.php';

function obtenerCitaParaGoogle(PDO $pdo, int $citaId): ?array
{
    $sql = "
        SELECT
            c.id,
            c.fecha,
            c.hora,
            c.motivo_consulta,
            c.estado,
            c.google_event_id,
            p.nombre AS paciente_nombre,
            p.telefono AS paciente_telefono,
            COALESCE(d.nombre, 'Doctor no asignado') AS doctor_nombre
        FROM citas c
        JOIN pacientes p ON p.id = c.paciente_id
        LEFT JOIN doctores d ON d.cedula = c.cedula_doctor
        WHERE c.id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$citaId]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    return $cita ?: null;
}

function guardarGoogleEventIdEnCita(PDO $pdo, int $citaId, ?string $googleEventId): void
{
    $stmt = $pdo->prepare('UPDATE citas SET google_event_id = ? WHERE id = ?');
    $stmt->execute([$googleEventId, $citaId]);
}

function esErrorGoogleNotFound(Throwable $e): bool
{
    $mensaje = $e->getMessage();

    return stripos($mensaje, 'Not Found') !== false
        || stripos($mensaje, '"code": 404') !== false;
}

function sincronizarCitaConGoogle(PDO $pdo, int $citaId): array
{
    $cita = obtenerCitaParaGoogle($pdo, $citaId);

    if (!$cita) {
        throw new Exception('La cita no existe para sincronizar con Google.');
    }

    $estado = strtolower(trim((string)($cita['estado'] ?? '')));
    $googleEventId = trim((string)($cita['google_event_id'] ?? ''));

    if ($estado === 'confirmada') {
        if ($googleEventId === '') {
            $evento = googleCalendarCreateEvent($cita);
            guardarGoogleEventIdEnCita($pdo, $citaId, $evento->getId());

            return [
                'accion' => 'creado',
                'google_event_id' => $evento->getId(),
            ];
        }

        $evento = googleCalendarUpdateEvent($googleEventId, $cita);

        return [
            'accion' => 'actualizado',
            'google_event_id' => $evento->getId(),
        ];
    }

    if ($estado === 'atendida') {
        if ($googleEventId === '') {
            return [
                'accion' => 'sin_cambios',
                'google_event_id' => null,
            ];
        }

        $evento = googleCalendarUpdateEvent($googleEventId, $cita);

        return [
            'accion' => 'actualizado',
            'google_event_id' => $evento->getId(),
        ];
    }

    if (in_array($estado, ['cancelada', 'pendiente'], true)) {
        if ($googleEventId !== '') {
            try {
                googleCalendarDeleteEvent($googleEventId);
            } catch (Throwable $e) {
                if (!esErrorGoogleNotFound($e)) {
                    throw $e;
                }
            }

            guardarGoogleEventIdEnCita($pdo, $citaId, null);

            return [
                'accion' => 'eliminado',
                'google_event_id' => null,
            ];
        }

        return [
            'accion' => 'sin_cambios',
            'google_event_id' => null,
        ];
    }

    return [
        'accion' => 'sin_cambios',
        'google_event_id' => $googleEventId !== '' ? $googleEventId : null,
    ];
}