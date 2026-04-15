<?php

function pmTableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function pmColumnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
    $stmt->execute([$column]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/* 🔥 USUARIOS */
function pmEnsureUsuariosStructure(PDO $pdo): void {
    if (!pmTableExists($pdo, 'usuarios')) {
        $pdo->exec("CREATE TABLE usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            correo VARCHAR(255) NOT NULL UNIQUE,
            nombre VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol ENUM('admin','doctor','secretaria') NOT NULL DEFAULT 'secretaria',
            activo TINYINT(1) NOT NULL DEFAULT 1,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

/* 🔥 PAGOS */
function pmEnsurePagosStructure(PDO $pdo): void {

    if (!pmTableExists($pdo, 'pagos')) {
        $pdo->exec("CREATE TABLE pagos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            paciente_id INT NULL,
            cita_id INT NULL,
            tratamiento_id INT NULL,
            concepto VARCHAR(150) NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            metodo_pago VARCHAR(50) NOT NULL DEFAULT 'efectivo',
            estado VARCHAR(30) NOT NULL DEFAULT 'pagado',
            referencia VARCHAR(120),
            fecha_pago DATE NOT NULL,
            observaciones TEXT,
            registrado_por INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX (cita_id),
            INDEX (tratamiento_id),

            FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL,
            FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

/* 🔥 TRATAMIENTOS */
function pmEnsureTratamientosStructure(PDO $pdo): void {

    /* CATÁLOGO */
    if (!pmTableExists($pdo, 'tratamientos_catalogo')) {
        $pdo->exec("CREATE TABLE tratamientos_catalogo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /* CITA TRATAMIENTOS */
    if (!pmTableExists($pdo, 'cita_tratamientos')) {
       $pdo->exec("CREATE TABLE cita_tratamientos (
       id INT AUTO_INCREMENT PRIMARY KEY,
       cita_id INT NOT NULL,
       tratamiento_id INT NOT NULL,
       paciente_id INT NULL,
       cantidad INT NOT NULL DEFAULT 1,
       precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
       subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
       estado VARCHAR(30) NOT NULL DEFAULT 'planeado',
       notas TEXT,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
       INDEX (cita_id),
       INDEX (tratamiento_id),
       INDEX (paciente_id),
       FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
       FOREIGN KEY (tratamiento_id) REFERENCES tratamientos_catalogo(id) ON DELETE RESTRICT,
       FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); 
    }
}

/* 🔥 PLANES */
function pmEnsurePlanesStructure(PDO $pdo): void {

    if (!pmTableExists($pdo, 'planes_tratamiento')) {
        $pdo->exec("CREATE TABLE planes_tratamiento (
            id INT AUTO_INCREMENT PRIMARY KEY,
            paciente_id INT NOT NULL,
            cita_id INT NULL,
            titulo VARCHAR(180) NOT NULL,
            diagnostico TEXT,
            objetivo TEXT,
            estado VARCHAR(30) DEFAULT 'propuesto',
            total_estimado DECIMAL(10,2) DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX (paciente_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmTableExists($pdo, 'plan_tratamiento_fases')) {
        $pdo->exec("CREATE TABLE plan_tratamiento_fases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            nombre VARCHAR(160) NOT NULL,
            descripcion TEXT,
            orden INT DEFAULT 1,
            estado VARCHAR(30) DEFAULT 'pendiente',
            fecha_objetivo DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmTableExists($pdo, 'plan_tratamiento_items')) {
        $pdo->exec("CREATE TABLE plan_tratamiento_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            fase_id INT NULL,
            tratamiento_id INT NOT NULL,
            cantidad INT DEFAULT 1,
            precio_unitario DECIMAL(10,2) DEFAULT 0.00,
            subtotal DECIMAL(10,2) DEFAULT 0.00,
            estado VARCHAR(30) DEFAULT 'pendiente',
            notas TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX (plan_id),
            INDEX (tratamiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

/* 🔥 ORDEN CORRECTO */
function ensureProfessionalModules(PDO $pdo): void {

    // ⚠️ Estas tablas deben existir antes
    // pacientes y citas deben existir en tu sistema

    pmEnsureUsuariosStructure($pdo);
    pmEnsureTratamientosStructure($pdo);
    pmEnsurePagosStructure($pdo);
    pmEnsurePlanesStructure($pdo);
}

function pmUsuariosActivosExpr(PDO $pdo): string {
    return pmColumnExists($pdo, 'usuarios', 'activo') ? 'COALESCE(activo, 1)' : '1';
}

function pmFormatoMoneda(float $monto): string {
    return '$' . number_format($monto, 2);
}

function pmTratamientosActivos(PDO $pdo): array {
    ensureProfessionalModules($pdo);
    return $pdo->query("SELECT id, nombre, descripcion, precio_base, activo FROM tratamientos_catalogo WHERE COALESCE(activo,1)=1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pmResumenCuentaCita(PDO $pdo, int $citaId): array {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(subtotal),0) FROM cita_tratamientos WHERE cita_id = ?");
    $stmt->execute([$citaId]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE cita_id = ? AND estado IN ('pagado','parcial')");
    $stmt->execute([$citaId]);
    $abonado = (float)$stmt->fetchColumn();

    return [
        'total' => $total,
        'abonado' => $abonado,
        'saldo' => max(0, $total - $abonado),
    ];
}

function pmEstadoCuentaDesdeSaldos(float $total, float $abonado): string {
    if ($total <= 0.0) {
        return 'sin_cargo';
    }
    if ($abonado <= 0.0) {
        return 'pendiente';
    }
    if ($abonado + 0.009 < $total) {
        return 'parcial';
    }
    return 'pagado';
}

function pmCitasCobroPendiente(PDO $pdo): array {
    ensureProfessionalModules($pdo);
    $sql = "SELECT 
                c.id,
                c.paciente_id,
                p.nombre AS paciente,
                c.fecha,
                c.hora,
                COALESCE(SUM(ct.subtotal),0) AS total_tratamientos,
                (
                    SELECT COALESCE(SUM(pg.monto),0)
                    FROM pagos pg
                    WHERE pg.cita_id = c.id AND pg.estado IN ('pagado','parcial')
                ) AS abonado,
                GROUP_CONCAT(tc.nombre SEPARATOR ', ') AS tratamientos
            FROM citas c
            JOIN pacientes p ON p.id = c.paciente_id
            JOIN cita_tratamientos ct ON ct.cita_id = c.id
            JOIN tratamientos_catalogo tc ON tc.id = ct.tratamiento_id
            GROUP BY c.id, c.paciente_id, p.nombre, c.fecha, c.hora
            ORDER BY c.fecha DESC, c.hora DESC";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) {
        $row['total_tratamientos'] = (float)$row['total_tratamientos'];
        $row['abonado'] = (float)$row['abonado'];
        $row['saldo'] = max(0, $row['total_tratamientos'] - $row['abonado']);
        $row['estado_cuenta'] = pmEstadoCuentaDesdeSaldos($row['total_tratamientos'], $row['abonado']);
    }
    unset($row);

    return $rows;
}

function pmResumenFinancieroPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(subtotal),0) FROM cita_tratamientos WHERE paciente_id = ?');
    $stmt->execute([$pacienteId]);
    $cargos = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE paciente_id = ? AND estado IN ('pagado','parcial')");
    $stmt->execute([$pacienteId]);
    $abonos = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(DISTINCT cita_id) FROM cita_tratamientos WHERE paciente_id = ? AND cita_id IS NOT NULL');
    $stmt->execute([$pacienteId]);
    $citasConCargo = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM pagos WHERE paciente_id = ?');
    $stmt->execute([$pacienteId]);
    $movimientos = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM planes_tratamiento WHERE paciente_id = ?');
    $stmt->execute([$pacienteId]);
    $planes = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(total_estimado),0) FROM planes_tratamiento WHERE paciente_id = ?');
    $stmt->execute([$pacienteId]);
    $estimadoPlanes = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_estimado),0) FROM planes_tratamiento WHERE paciente_id = ? AND estado IN ('aprobado','en_proceso')");
    $stmt->execute([$pacienteId]);
    $estimadoVigentePlanes = (float)$stmt->fetchColumn();

    return [
        'cargos' => $cargos,
        'abonos' => $abonos,
        'saldo' => max(0, $cargos - $abonos),
        'estado' => pmEstadoCuentaDesdeSaldos($cargos, $abonos),
        'citas_con_cargo' => $citasConCargo,
        'movimientos' => $movimientos,
        'planes' => $planes,
        'estimado_planes' => $estimadoPlanes,
        'estimado_planes_vigentes' => $estimadoVigentePlanes,
    ];
}

function pmDetallePlanesFinancierosPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("SELECT pt.id, pt.titulo, pt.estado, pt.total_estimado, pt.cita_id,
        (SELECT COUNT(*) FROM plan_tratamiento_fases pf WHERE pf.plan_id = pt.id) AS fases,
        (SELECT COUNT(*) FROM plan_tratamiento_items pi WHERE pi.plan_id = pt.id) AS items
        FROM planes_tratamiento pt
        WHERE pt.paciente_id = ?
        ORDER BY pt.id DESC");
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pmDetalleItemsPlaneadosPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("SELECT
        pt.id AS plan_id,
        pt.titulo AS plan,
        pt.estado AS plan_estado,
        pf.nombre AS fase,
        tc.nombre AS tratamiento,
        pi.cantidad,
        pi.precio_unitario,
        pi.subtotal,
        pi.estado,
        pi.notas
        FROM plan_tratamiento_items pi
        JOIN planes_tratamiento pt ON pt.id = pi.plan_id
        LEFT JOIN plan_tratamiento_fases pf ON pf.id = pi.fase_id
        JOIN tratamientos_catalogo tc ON tc.id = pi.tratamiento_id
        WHERE pt.paciente_id = ?
        ORDER BY pt.id DESC, COALESCE(pf.orden, 9999) ASC, pi.id ASC");
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pmPlanesPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare("SELECT pt.*, 
        (SELECT COUNT(*) FROM plan_tratamiento_fases pf WHERE pf.plan_id = pt.id) AS fases,
        (SELECT COUNT(*) FROM plan_tratamiento_items pi WHERE pi.plan_id = pt.id) AS items
        FROM planes_tratamiento pt
        WHERE pt.paciente_id = ?
        ORDER BY pt.id DESC");
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pmRecalcularPlanTotal(PDO $pdo, int $planId): void {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(subtotal),0) FROM plan_tratamiento_items WHERE plan_id = ?');
    $stmt->execute([$planId]);
    $total = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare('UPDATE planes_tratamiento SET total_estimado = ? WHERE id = ?');
    $stmt->execute([$total, $planId]);
}


function pmBuscarTratamientoPorAlias(PDO $pdo, array $aliases): ?array {
    ensureProfessionalModules($pdo);

    $aliases = array_values(array_filter(array_map(static function ($alias) {
        return trim((string)$alias);
    }, $aliases)));

    if (!$aliases) {
        return null;
    }

    foreach ($aliases as $alias) {
        $stmt = $pdo->prepare('SELECT * FROM tratamientos_catalogo WHERE COALESCE(activo,1)=1 AND LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1');
        $stmt->execute([$alias]);
        $tratamiento = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tratamiento) {
            return $tratamiento;
        }
    }

    foreach ($aliases as $alias) {
        $stmt = $pdo->prepare('SELECT * FROM tratamientos_catalogo WHERE COALESCE(activo,1)=1 AND LOWER(nombre) LIKE LOWER(?) ORDER BY id ASC LIMIT 1');
        $stmt->execute(['%' . $alias . '%']);
        $tratamiento = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tratamiento) {
            return $tratamiento;
        }
    }

    return null;
}

function pmObtenerOCrearPlanOdontograma(PDO $pdo, int $pacienteId): int {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("SELECT id
        FROM planes_tratamiento
        WHERE paciente_id = ? AND estado IN ('propuesto','aprobado','en_proceso')
        ORDER BY id DESC
        LIMIT 1");
    $stmt->execute([$pacienteId]);
    $planId = (int)$stmt->fetchColumn();

    if ($planId > 0) {
        return $planId;
    }

    $stmt = $pdo->prepare('INSERT INTO planes_tratamiento (paciente_id, titulo, diagnostico, objetivo, estado) VALUES (?,?,?,?,?)');
    $stmt->execute([
        $pacienteId,
        'Cotización desde odontograma',
        'Plan generado desde hallazgos del odontograma.',
        'Convertir hallazgos clínicos en tratamiento cotizable y medible.',
        'propuesto',
    ]);

    return (int)$pdo->lastInsertId();
}

function pmAgregarItemPlanSiNoExiste(PDO $pdo, int $planId, int $tratamientoId, int $cantidad, string $estado, ?string $notas = null, ?int $faseId = null): int {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare('SELECT id, precio_base FROM tratamientos_catalogo WHERE id = ? LIMIT 1');
    $stmt->execute([$tratamientoId]);
    $tratamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tratamiento) {
        throw new RuntimeException('Tratamiento no disponible para cotización.');
    }

    $cantidad = max(1, $cantidad);
    $notasNormalizadas = trim((string)$notas);

    $stmt = $pdo->prepare("SELECT id FROM plan_tratamiento_items WHERE plan_id = ? AND tratamiento_id = ? AND COALESCE(notas,'') = ? LIMIT 1");
    $stmt->execute([$planId, $tratamientoId, $notasNormalizadas]);
    $itemExistenteId = (int)$stmt->fetchColumn();
    if ($itemExistenteId > 0) {
        return $itemExistenteId;
    }

    $precio = (float)$tratamiento['precio_base'];
    $subtotal = $precio * $cantidad;
    $stmt = $pdo->prepare('INSERT INTO plan_tratamiento_items (plan_id, fase_id, tratamiento_id, cantidad, precio_unitario, subtotal, estado, notas) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$planId, $faseId, $tratamientoId, $cantidad, $precio, $subtotal, $estado !== '' ? $estado : 'pendiente', $notasNormalizadas !== '' ? $notasNormalizadas : null]);

    pmRecalcularPlanTotal($pdo, $planId);

    return (int)$pdo->lastInsertId();
}
