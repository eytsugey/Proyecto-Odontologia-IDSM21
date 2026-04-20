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
        return;
    }

    if (!pmColumnExists($pdo, 'usuarios', 'activo')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1");
    }
    if (!pmColumnExists($pdo, 'usuarios', 'creado_en')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    }
    if (!pmColumnExists($pdo, 'usuarios', 'actualizado_en')) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    $rolInfo = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'rol'")->fetch(PDO::FETCH_ASSOC);
    if ($rolInfo && isset($rolInfo['Type'])) {
        $type = strtolower((string)$rolInfo['Type']);
        if (str_contains($type, 'enum(') && (!str_contains($type, "'admin'") || !str_contains($type, "'doctor'") || !str_contains($type, "'secretaria'"))) {
            $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('admin','administrador','doctor','secretaria') NOT NULL DEFAULT 'secretaria'");
        }
    }
}

function pmEnsurePagosStructure(PDO $pdo): void {
    if (!pmTableExists($pdo, 'pagos')) {
        $pdo->exec("CREATE TABLE pagos (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            paciente_id BIGINT NULL,
            cita_id BIGINT NULL,
            tratamiento_id BIGINT NULL,
            concepto VARCHAR(150) NOT NULL,
            monto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            metodo_pago VARCHAR(50) NOT NULL DEFAULT 'efectivo',
            estado VARCHAR(30) NOT NULL DEFAULT 'pagado',
            referencia VARCHAR(120) DEFAULT NULL,
            fecha_pago DATE NOT NULL,
            observaciones TEXT DEFAULT NULL,
            registrado_por INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pagos_cita (cita_id),
            INDEX idx_pagos_tratamiento (tratamiento_id),
            CONSTRAINT fk_pagos_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL,
            CONSTRAINT fk_pagos_usuario FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmColumnExists($pdo, 'pagos', 'cita_id')) {
        $pdo->exec("ALTER TABLE pagos ADD COLUMN cita_id BIGINT NULL AFTER paciente_id");
        $pdo->exec("ALTER TABLE pagos ADD INDEX idx_pagos_cita (cita_id)");
    }
    if (!pmColumnExists($pdo, 'pagos', 'tratamiento_id')) {
        $pdo->exec("ALTER TABLE pagos ADD COLUMN tratamiento_id BIGINT NULL AFTER cita_id");
        $pdo->exec("ALTER TABLE pagos ADD INDEX idx_pagos_tratamiento (tratamiento_id)");
    }
}

function pmEnsureTratamientosStructure(PDO $pdo): void {
    if (!pmTableExists($pdo, 'tratamientos_catalogo')) {
        $pdo->exec("CREATE TABLE tratamientos_catalogo (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT NULL,
            precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            activo TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmTableExists($pdo, 'cita_tratamientos')) {
        $pdo->exec("CREATE TABLE cita_tratamientos (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            cita_id BIGINT NOT NULL,
            tratamiento_id BIGINT NOT NULL,
            paciente_id BIGINT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estado VARCHAR(30) NOT NULL DEFAULT 'planeado',
            notas TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ct_cita (cita_id),
            INDEX idx_ct_tratamiento (tratamiento_id),
            INDEX idx_ct_paciente (paciente_id),
            CONSTRAINT fk_ct_tratamiento FOREIGN KEY (tratamiento_id) REFERENCES tratamientos_catalogo(id) ON DELETE RESTRICT,
            CONSTRAINT fk_ct_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function pmEnsurePlanesStructure(PDO $pdo): void {
    if (!pmTableExists($pdo, 'planes_tratamiento')) {
        $pdo->exec("CREATE TABLE planes_tratamiento (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            paciente_id BIGINT NOT NULL,
            cita_id BIGINT NULL,
            tipo VARCHAR(30) NOT NULL DEFAULT 'general',
            titulo VARCHAR(180) NOT NULL,
            diagnostico TEXT NULL,
            objetivo TEXT NULL,
            estado VARCHAR(30) NOT NULL DEFAULT 'propuesto',
            total_estimado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_pt_paciente (paciente_id),
            INDEX idx_pt_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmColumnExists($pdo, 'planes_tratamiento', 'tipo')) {
        $pdo->exec("ALTER TABLE planes_tratamiento ADD COLUMN tipo VARCHAR(30) NOT NULL DEFAULT 'general' AFTER cita_id");
        $pdo->exec("ALTER TABLE planes_tratamiento ADD INDEX idx_pt_tipo (tipo)");
    }

    if (!pmTableExists($pdo, 'plan_tratamiento_fases')) {
        $pdo->exec("CREATE TABLE plan_tratamiento_fases (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            plan_id BIGINT NOT NULL,
            nombre VARCHAR(160) NOT NULL,
            descripcion TEXT NULL,
            orden INT NOT NULL DEFAULT 1,
            estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
            fecha_objetivo DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ptf_plan (plan_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    if (!pmTableExists($pdo, 'plan_tratamiento_items')) {
        $pdo->exec("CREATE TABLE plan_tratamiento_items (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            plan_id BIGINT NOT NULL,
            fase_id BIGINT NULL,
            tratamiento_id BIGINT NOT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
            notas TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_pti_plan (plan_id),
            INDEX idx_pti_fase (fase_id),
            INDEX idx_pti_tratamiento (tratamiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}


function pmEnsureHistoriaTratamientosStructure(PDO $pdo): void {
    if (!pmTableExists($pdo, 'historia_tratamientos_realizados')) {
        $pdo->exec("CREATE TABLE historia_tratamientos_realizados (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            paciente_id BIGINT NOT NULL,
            plan_id BIGINT NULL,
            plan_item_id BIGINT NOT NULL,
            tratamiento_id BIGINT NOT NULL,
            tratamiento_nombre VARCHAR(180) NOT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            notas TEXT NULL,
            fecha_realizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            registrado_por INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_htr_plan_item (plan_item_id),
            INDEX idx_htr_paciente (paciente_id),
            INDEX idx_htr_tratamiento (tratamiento_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

function pmEliminarTratamientoRealizadoHistoria(PDO $pdo, int $planItemId): void {
    if ($planItemId <= 0) {
        return;
    }
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare('DELETE FROM historia_tratamientos_realizados WHERE plan_item_id = ?');
    $stmt->execute([$planItemId]);
}

function pmSincronizarTratamientoRealizadoHistoria(PDO $pdo, int $planItemId, ?int $registradoPor = null): void
{
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("
        SELECT 
            pti.id,
            pti.plan_id,
            pti.tratamiento_id,
            pti.cantidad,
            pti.precio_unitario,
            pti.subtotal,
            pti.estado,
            pti.notas,
            pt.paciente_id,
            tc.nombre AS tratamiento_nombre
        FROM plan_tratamiento_items pti
        JOIN planes_tratamiento pt ON pt.id = pti.plan_id
        JOIN tratamientos_catalogo tc ON tc.id = pti.tratamiento_id
        WHERE pti.id = ?
        LIMIT 1
    ");
    $stmt->execute([$planItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        return;
    }

    $estado = strtolower(trim((string)($item['estado'] ?? '')));

    $stmt = $pdo->prepare("
        SELECT id
        FROM historia_tratamientos_realizados
        WHERE plan_item_id = ?
        LIMIT 1
    ");
    $stmt->execute([$planItemId]);
    $existenteId = $stmt->fetchColumn();

    if ($estado !== 'realizado') {
        if ($existenteId) {
            $stmt = $pdo->prepare("DELETE FROM historia_tratamientos_realizados WHERE id = ?");
            $stmt->execute([$existenteId]);
        }
        return;
    }

    $datos = [
        $item['paciente_id'],
        $item['plan_id'] !== null ? (int)$item['plan_id'] : null,
        $planItemId,
        (int)$item['tratamiento_id'],
        (string)$item['tratamiento_nombre'],
        max(1, (int)$item['cantidad']),
        (float)$item['precio_unitario'],
        (float)$item['subtotal'],
        trim((string)($item['notas'] ?? '')) !== '' ? (string)$item['notas'] : null,
        $registradoPor,
    ];

    if ($existenteId) {
        $stmt = $pdo->prepare("
            UPDATE historia_tratamientos_realizados
            SET
                paciente_id = ?,
                plan_id = ?,
                tratamiento_id = ?,
                tratamiento_nombre = ?,
                cantidad = ?,
                precio_unitario = ?,
                subtotal = ?,
                notas = ?,
                fecha_realizacion = NOW(),
                registrado_por = ?
            WHERE plan_item_id = ?
        ");
        $stmt->execute([
            $datos[0],
            $datos[1],
            $datos[3],
            $datos[4],
            $datos[5],
            $datos[6],
            $datos[7],
            $datos[8],
            $datos[9],
            $planItemId,
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO historia_tratamientos_realizados
        (
            paciente_id,
            plan_id,
            plan_item_id,
            tratamiento_id,
            tratamiento_nombre,
            cantidad,
            precio_unitario,
            subtotal,
            notas,
            fecha_realizacion,
            registrado_por
        )
        VALUES (?,?,?,?,?,?,?,?,?,NOW(),?)
    ");
    $stmt->execute($datos);
}

function pmTratamientosRealizadosHistoriaPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare('SELECT * FROM historia_tratamientos_realizados WHERE paciente_id = ? ORDER BY fecha_realizacion DESC, id DESC');
    $stmt->execute([$pacienteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ensureProfessionalModules(PDO $pdo): void {
    pmEnsureUsuariosStructure($pdo);
    pmEnsurePagosStructure($pdo);
    pmEnsureTratamientosStructure($pdo);
    pmEnsurePlanesStructure($pdo);
    pmEnsureHistoriaTratamientosStructure($pdo);
}

function pmNormalizarTipoPlan(?string $tipo): string {
    $tipo = strtolower(trim((string)$tipo));
    return $tipo === 'ortodoncia' ? 'ortodoncia' : 'general';
}

function pmTituloPlanPorTipo(string $tipo): string {
    return pmNormalizarTipoPlan($tipo) === 'ortodoncia' ? 'Ortodoncia' : 'Plan de tratamiento';
}

function pmObtenerPlanPacientePorTipo(PDO $pdo, int $pacienteId, string $tipo = 'general'): ?array {
    ensureProfessionalModules($pdo);
    $tipo = pmNormalizarTipoPlan($tipo);

    $stmt = $pdo->prepare("SELECT * FROM planes_tratamiento WHERE paciente_id = ? AND tipo = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$pacienteId, $tipo]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    return $plan ?: null;
}

function pmObtenerOCrearPlanPacientePorTipo(PDO $pdo, int $pacienteId, string $tipo = 'general', array $data = []): int {
    ensureProfessionalModules($pdo);
    $tipo = pmNormalizarTipoPlan($tipo);
    $existente = pmObtenerPlanPacientePorTipo($pdo, $pacienteId, $tipo);
    if ($existente) {
        return (int)$existente['id'];
    }

    $titulo = trim((string)($data['titulo'] ?? pmTituloPlanPorTipo($tipo)));
    $diagnostico = trim((string)($data['diagnostico'] ?? ''));
    $objetivo = trim((string)($data['objetivo'] ?? ''));
    $estado = trim((string)($data['estado'] ?? 'propuesto')) ?: 'propuesto';
    $citaId = (int)($data['cita_id'] ?? 0);

    $stmt = $pdo->prepare('INSERT INTO planes_tratamiento (paciente_id, cita_id, tipo, titulo, diagnostico, objetivo, estado) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $pacienteId,
        $citaId > 0 ? $citaId : null,
        $tipo,
        $titulo,
        $diagnostico !== '' ? $diagnostico : null,
        $objetivo !== '' ? $objetivo : null,
        $estado,
    ]);

    return (int)$pdo->lastInsertId();
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


function pmEstadoPagoPlanItem(?string $estadoItem): string {
    $estado = strtolower(trim((string)$estadoItem));
    return in_array($estado, ['realizado', 'pagado'], true) ? 'pagado' : 'pendiente';
}

function pmPagosPacienteDisponiblesPorTratamiento(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare("SELECT tratamiento_id, COALESCE(SUM(monto),0) AS abonado
        FROM pagos
        WHERE paciente_id = ?
          AND tratamiento_id IS NOT NULL
          AND estado IN ('pagado','parcial')
        GROUP BY tratamiento_id");
    $stmt->execute([$pacienteId]);

    $mapa = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $tratamientoId = (int)($row['tratamiento_id'] ?? 0);
        if ($tratamientoId > 0) {
            $mapa[$tratamientoId] = (float)($row['abonado'] ?? 0);
        }
    }

    return $mapa;
}

function pmAplicarPagosAItemsPlan(array $items, array $pagosDisponibles): array {
    foreach ($items as &$item) {
        $tratamientoId = (int)($item['tratamiento_id'] ?? 0);
        $subtotal = (float)($item['subtotal'] ?? 0);
        $abonadoItem = 0.0;

        if ($tratamientoId > 0 && isset($pagosDisponibles[$tratamientoId])) {
            $disponible = (float)$pagosDisponibles[$tratamientoId];
            if ($disponible > 0) {
                $abonadoItem = min($subtotal, $disponible);
                $pagosDisponibles[$tratamientoId] = max(0, $disponible - $abonadoItem);
            }
        }

        $item['abonado'] = $abonadoItem;
        $item['saldo'] = max(0, $subtotal - $abonadoItem);
        $item['estado_pago'] = $abonadoItem + 0.009 >= $subtotal && $subtotal > 0
            ? 'pagado'
            : ($abonadoItem > 0 ? 'parcial' : pmEstadoPagoPlanItem($item['estado'] ?? ''));
    }
    unset($item);

    return $items;
}

function pmResumenPlanGeneralPaciente(PDO $pdo, int $pacienteId): array {
    ensureProfessionalModules($pdo);

    $items = pmDetalleItemsPlaneadosPacientePorTipo($pdo, $pacienteId, 'general');
    $tratamientos = count($items);
    $pagados = 0;
    $estimado = 0.0;

    foreach ($items as $item) {
        $estimado += (float)($item['subtotal'] ?? 0);

        if (($item['estado_pago'] ?? 'pendiente') === 'pagado') {
            $pagados++;
        }
    }

    return [
        'tratamientos' => $tratamientos,
        'pagados' => $pagados,
        'pendientes' => max(0, $tratamientos - $pagados),
        'estimado' => $estimado,
    ];
}

function pmDetallePlanesPacientePorTipo(PDO $pdo, int $pacienteId, string $tipo = 'general'): array {
    ensureProfessionalModules($pdo);
    $tipo = pmNormalizarTipoPlan($tipo);

    $stmt = $pdo->prepare("SELECT pt.id, pt.tipo, pt.titulo, pt.estado, pt.total_estimado, pt.cita_id,
        (SELECT COUNT(*) FROM plan_tratamiento_items pi WHERE pi.plan_id = pt.id) AS items
        FROM planes_tratamiento pt
        WHERE pt.paciente_id = ? AND COALESCE(pt.tipo,'general') = ?
        ORDER BY pt.id DESC");
    $stmt->execute([$pacienteId, $tipo]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function pmDetalleItemsPlaneadosPacientePorTipo(PDO $pdo, int $pacienteId, string $tipo = 'general'): array {
    ensureProfessionalModules($pdo);
    $tipo = pmNormalizarTipoPlan($tipo);

    $stmt = $pdo->prepare("SELECT
        pt.id AS plan_id,
        pt.tipo AS plan_tipo,
        pt.titulo AS plan,
        pt.estado AS plan_estado,
        pi.id,
        pi.tratamiento_id,
        tc.nombre AS tratamiento,
        pi.cantidad,
        pi.precio_unitario,
        pi.subtotal,
        pi.estado,
        pi.notas
        FROM plan_tratamiento_items pi
        JOIN planes_tratamiento pt ON pt.id = pi.plan_id
        JOIN tratamientos_catalogo tc ON tc.id = pi.tratamiento_id
        WHERE pt.paciente_id = ? AND COALESCE(pt.tipo,'general') = ?
        ORDER BY pt.id DESC, pi.id ASC");
    $stmt->execute([$pacienteId, $tipo]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $pagosDisponibles = pmPagosPacienteDisponiblesPorTratamiento($pdo, $pacienteId);
    return pmAplicarPagosAItemsPlan($items, $pagosDisponibles);
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

    $stmt = $pdo->prepare("SELECT pt.id, pt.tipo, pt.titulo, pt.estado, pt.total_estimado, pt.cita_id,
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
        pt.tipo AS plan_tipo,
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

function pmPlanesPaciente(PDO $pdo, int $pacienteId, ?string $tipo = null): array {
    ensureProfessionalModules($pdo);
    $sql = "SELECT pt.*, 
        (SELECT COUNT(*) FROM plan_tratamiento_fases pf WHERE pf.plan_id = pt.id) AS fases,
        (SELECT COUNT(*) FROM plan_tratamiento_items pi WHERE pi.plan_id = pt.id) AS items
        FROM planes_tratamiento pt
        WHERE pt.paciente_id = ?";
    $params = [$pacienteId];

    if ($tipo !== null) {
        $sql .= " AND pt.tipo = ?";
        $params[] = pmNormalizarTipoPlan($tipo);
    }

    $sql .= " ORDER BY pt.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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
    return pmObtenerOCrearPlanPacientePorTipo($pdo, $pacienteId, 'general', [
        'titulo' => 'Plan de tratamiento',
        'diagnostico' => 'Plan generado desde hallazgos del odontograma.',
        'objetivo' => 'Convertir hallazgos clínicos en tratamiento sugerido.',
        'estado' => 'propuesto',
    ]);
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
