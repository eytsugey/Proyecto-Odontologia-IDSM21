CREATE DATABASE IF NOT EXISTS odontologia_db;
USE odontologia_db;

-- =========================
-- USUARIOS (ANTES tb_user)
-- =========================

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    correo VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('doctor','secretaria') NOT NULL,
    restablecer_pass VARCHAR(255) DEFAULT NULL,
    hora_restablecer DATETIME DEFAULT NULL
);

-- =========================
-- DOCTORES (ANTES tb_Doctores)
-- =========================
CREATE TABLE doctores (
    cedula VARCHAR(255) PRIMARY KEY,
    nombre VARCHAR(90) NOT NULL,
    foto MEDIUMTEXT,
    especialidad TEXT,
    telefono VARCHAR(10)
);

-- =========================
-- PACIENTES (ANTES tb_Pacientes)
-- =========================
CREATE TABLE pacientes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(90) NOT NULL,
    sexo VARCHAR(10) NOT NULL,
    edad INT NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    lugar_nacimiento TEXT,
    estado_civil VARCHAR(100),
    domicilio TEXT,
    colonia TEXT,
    ocupacion TEXT,
    telefono VARCHAR(10) NOT NULL
);

-- =========================
-- HISTORIA CLINICA (ANTES tb_HistoriaClinica)
-- =========================
CREATE TABLE historias_clinicas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT NOT NULL UNIQUE,

    -- Antecedentes
    alergico_med_comida TINYINT(1) DEFAULT 0,
    cual_alergia TEXT,
    intervencion TINYINT(1) DEFAULT 0,
    cual_intervencion TEXT,
    reac_anestesia TINYINT(1) DEFAULT 0,
    cual_reaccion TEXT,
    coagulacion_sang TINYINT(1) DEFAULT 0,
    diabetes TINYINT(1) DEFAULT 0,
    med_diabetes TEXT,
    pres_alta TINYINT(1) DEFAULT 0,
    med_pres_alta TEXT,
    pres_baja TINYINT(1) DEFAULT 0,
    med_pres_baja TEXT,
    enf_venereas TINYINT(1) DEFAULT 0,
    cuales_enf_ven TEXT,
    hepatitis TINYINT(1) DEFAULT 0,
    tipo_hepatitis VARCHAR(255),
    asma TINYINT(1) DEFAULT 0,
    enfermedad TINYINT(1) DEFAULT 0,
    cuales_enfermedad TEXT,
    drogas TINYINT(1) DEFAULT 0,
    cuales_drogas TEXT,
    toma_med TINYINT(1) DEFAULT 0,
    para_que_med TEXT,
    embarazo TINYINT(1) DEFAULT 0,
    mes_embarazo VARCHAR(10),

    -- Hábitos
    fuma TINYINT(1) DEFAULT 0,
    toma TINYINT(1) DEFAULT 0,
    farmaco_dep TINYINT(1) DEFAULT 0,
    cuales_habitos TEXT,

    -- Información general
    motivo_visita TEXT,
    primera_vez TINYINT(1) DEFAULT 0,
    ultima_visita DATE,
    dolor_boca TINYINT(1) DEFAULT 0,
    tipo VARCHAR(255),
    tipo_dientes VARCHAR(90),
    ta TEXT,
    pulso TEXT,
    oximetria TEXT,
    glucosa TEXT,
    diagnostico TEXT,

    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE
);

-- =========================
-- ODONTOGRAMA (IMPORTANTE)
-- =========================
CREATE TABLE odontograma_paciente (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT NOT NULL,
    diente VARCHAR(10) NOT NULL,
    estado VARCHAR(255),
    descripcion TEXT,
    UNIQUE KEY uk_odontograma_paciente_diente (paciente_id, diente),

    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE
);

-- =========================
-- PLAN DE TRATAMIENTO
-- =========================
CREATE TABLE plan_tratamiento (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT NOT NULL,
    tratamiento TEXT NOT NULL,
    cantidad BIGINT DEFAULT 1,
    precio DECIMAL(10,2) DEFAULT 0.00,
    estado TEXT,

    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE
);

-- =========================
-- CITAS
-- =========================
CREATE TABLE citas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT NOT NULL,
    cedula_doctor VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    motivo_consulta TEXT,
    estado VARCHAR(50) DEFAULT 'Pendiente',

    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE,
    FOREIGN KEY (cedula_doctor) REFERENCES doctores(cedula)
        ON DELETE CASCADE
);

-- =========================
-- REGISTRO DIARIO
-- =========================
CREATE TABLE registro_diario (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT,
    fecha DATE NOT NULL,
    nombre VARCHAR(90),
    tratamiento TEXT,
    doctor VARCHAR(90),
    precio DECIMAL(10,2) DEFAULT 0.00,
    gasto DECIMAL(10,2) DEFAULT 0.00,
    descripcion_gasto VARCHAR(255),
    ingreso DECIMAL(10,2) DEFAULT 0.00,
    moneda VARCHAR(10) DEFAULT 'MXN',

    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE SET NULL
);