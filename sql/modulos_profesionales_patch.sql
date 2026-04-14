USE odontologia_db;

ALTER TABLE usuarios
  MODIFY COLUMN rol ENUM('admin','administrador','doctor','secretaria') NOT NULL DEFAULT 'secretaria';

ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS creado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS tratamientos_catalogo (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    precio_base DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cita_tratamientos (
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
    INDEX idx_ct_paciente (paciente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
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
    INDEX idx_pagos_tratamiento (tratamiento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pagos
  ADD COLUMN IF NOT EXISTS cita_id BIGINT NULL AFTER paciente_id,
  ADD COLUMN IF NOT EXISTS tratamiento_id BIGINT NULL AFTER cita_id;


CREATE TABLE IF NOT EXISTS planes_tratamiento (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT NOT NULL,
    cita_id BIGINT NULL,
    titulo VARCHAR(180) NOT NULL,
    diagnostico TEXT NULL,
    objetivo TEXT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'propuesto',
    total_estimado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pt_paciente (paciente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_tratamiento_fases (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_tratamiento_items (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
