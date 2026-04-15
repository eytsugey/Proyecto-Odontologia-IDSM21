<?php
require_once __DIR__ . '/../config/app.php';

function tableExists(PDO $pdo, string $table): bool {
    static $cache = [];
    $key = spl_object_id($pdo) . ':' . $table;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function columnExists(PDO $pdo, string $table, string $column): bool {
    static $cache = [];
    $key = spl_object_id($pdo) . ':' . $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->execute([$table, $column]);
        return $cache[$key] = ((int)$stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        return $cache[$key] = false;
    }
}

function citasDoctorMode(PDO $pdo): string {
    if (columnExists($pdo, 'citas', 'cedula_doctor') && tableExists($pdo, 'doctores')) {
        return 'cedula';
    }

    if (columnExists($pdo, 'citas', 'doctor_id')) {
        return 'doctor_id';
    }

    return 'none';
}

function citasEstadoNormalizado(?string $estado): string {
    $estado = strtolower(trim((string)$estado));
    return match ($estado) {
        'confirmada', 'atendida', 'cancelada', 'pendiente' => $estado,
        default => 'pendiente',
    };
}

function obtenerDoctores(PDO $pdo): array {
    $mode = citasDoctorMode($pdo);

    if ($mode === 'cedula') {
        return $pdo->query('SELECT cedula AS valor, nombre FROM doctores ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    if ($mode === 'doctor_id') {
        return [['valor' => '1', 'nombre' => 'Doctor general']];
    }

    return [];
}

function obtenerDoctorPorDefecto(PDO $pdo): ?string {
    $doctores = obtenerDoctores($pdo);
    return $doctores[0]['valor'] ?? null;
}

function obtenerCampoDoctorCitas(PDO $pdo): ?string {
    return match (citasDoctorMode($pdo)) {
        'cedula' => 'cedula_doctor',
        'doctor_id' => 'doctor_id',
        default => null,
    };
}

function obtenerSQLListadoCitas(PDO $pdo): string {
    $mode = citasDoctorMode($pdo);

    if ($mode === 'cedula') {
        return "
            SELECT
                c.id,
                c.paciente_id,
                c.cedula_doctor AS doctor_valor,
                p.nombre AS paciente,
                d.nombre AS doctor,
                c.fecha,
                c.hora,
                c.motivo_consulta,
                LOWER(c.estado) AS estado
            FROM citas c
            JOIN pacientes p ON p.id = c.paciente_id
            LEFT JOIN doctores d ON d.cedula = c.cedula_doctor
            ORDER BY c.fecha DESC, c.hora ASC
        ";
    }

    return "
        SELECT
            c.id,
            c.paciente_id,
            CAST(c.doctor_id AS CHAR) AS doctor_valor,
            p.nombre AS paciente,
            CONCAT('Doctor ', c.doctor_id) AS doctor,
            c.fecha,
            c.hora,
            c.motivo_consulta,
            LOWER(c.estado) AS estado
        FROM citas c
        JOIN pacientes p ON p.id = c.paciente_id
        ORDER BY c.fecha DESC, c.hora ASC
    ";
}
