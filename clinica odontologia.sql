
USE clinica_dental;

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador','secretaria','odontologo') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    sexo ENUM('M','F') NOT NULL,
    fecha_nacimiento DATE,
    lugar_nacimiento VARCHAR(150),
    estado_civil VARCHAR(50),
    domicilio VARCHAR(200),
    colonia VARCHAR(100),
    ocupacion VARCHAR(100),
    telefono VARCHAR(20),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE citas (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('solicitada','confirmada','cancelada','atendida') DEFAULT 'solicitada',
    observaciones TEXT,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
);

CREATE TABLE historias_clinicas (
    id_historia INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT,
    alergias TEXT,
    cirugias_recientes TEXT,
    reaccion_anestesia TEXT,
    problemas_coagulacion BOOLEAN,
    diabetes BOOLEAN,
    medicamento_diabetes VARCHAR(150),
    presion_alta BOOLEAN,
    medicamento_presion_alta VARCHAR(150),
    presion_baja BOOLEAN,
    medicamento_presion_baja VARCHAR(150),
    otras_enfermedades TEXT,
    otros_medicamentos TEXT,
    embarazada BOOLEAN,
    meses_embarazo INT,
    fuma BOOLEAN,
    toma BOOLEAN,
    farmacodependiente BOOLEAN,
    enfermedades_venereas TEXT,
    hepatitis_tipo VARCHAR(20),
    asma BOOLEAN,
    habitos_perniciosos TEXT,
    motivo_consulta TEXT,
    primera_vez BOOLEAN,
    ultima_visita DATE,
    dolor_actual TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
);

CREATE TABLE signos_vitales (
    id_signos INT AUTO_INCREMENT PRIMARY KEY,
    id_historia INT,
    tension_arterial VARCHAR(20),
    pulso INT,
    oximetria DECIMAL(5,2),
    glucosa DECIMAL(5,2),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_historia) REFERENCES historias_clinicas(id_historia)
);

CREATE TABLE diagnosticos (
    id_diagnostico INT AUTO_INCREMENT PRIMARY KEY,
    id_historia INT,
    descripcion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_historia) REFERENCES historias_clinicas(id_historia)
);

CREATE TABLE odontograma (
    id_odontograma INT AUTO_INCREMENT PRIMARY KEY,
    id_historia INT,
    numero_pieza INT,
    estado VARCHAR(100),
    FOREIGN KEY (id_historia) REFERENCES historias_clinicas(id_historia)
);

CREATE TABLE tratamientos (
    id_tratamiento INT AUTO_INCREMENT PRIMARY KEY,
    id_historia INT,
    nombre_tratamiento VARCHAR(150),
    cantidad INT,
    precio DECIMAL(10,2),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_historia) REFERENCES historias_clinicas(id_historia)
);

CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_tratamiento INT,
    fecha DATE,
    metodo_pago ENUM('efectivo','transferencia','tarjeta') DEFAULT 'efectivo',
    monto_pagado DECIMAL(10,2),
    saldo DECIMAL(10,2),
    FOREIGN KEY (id_tratamiento) REFERENCES tratamientos(id_tratamiento)
);

CREATE TABLE consentimientos (
    id_consentimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_historia INT,
    fecha DATE,
    firmado BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    FOREIGN KEY (id_historia) REFERENCES historias_clinicas(id_historia)
);


CREATE VIEW vista_ingresos_semanales AS
SELECT 
    YEAR(fecha) AS anio,
    WEEK(fecha,1) AS semana,
    SUM(monto_pagado) AS total_ingresos
FROM pagos
GROUP BY anio, semana;

CREATE VIEW vista_ingresos_por_paciente AS
SELECT 
    p.nombre,
    SUM(pg.monto_pagado) AS total_pagado
FROM pacientes p
JOIN historias_clinicas hc ON p.id_paciente = hc.id_paciente
JOIN tratamientos t ON hc.id_historia = t.id_historia
JOIN pagos pg ON t.id_tratamiento = pg.id_tratamiento
GROUP BY p.id_paciente;

CREATE VIEW vista_tratamientos_frecuentes AS
SELECT 
    t.nombre_tratamiento,
    COUNT(*) AS veces_realizado,
    SUM(pg.monto_pagado) AS ingreso_total
FROM tratamientos t
JOIN pagos pg ON t.id_tratamiento = pg.id_tratamiento
GROUP BY t.nombre_tratamiento;

CREATE VIEW vista_citas_solicitadas AS
SELECT 
    c.id_cita,
    p.nombre,
    c.fecha,
    c.hora,
    c.estado
FROM citas c
JOIN pacientes p ON c.id_paciente = p.id_paciente
WHERE c.estado = 'solicitada';

CREATE VIEW vista_historia_resumen AS
SELECT 
    p.nombre,
    hc.motivo_consulta,
    d.descripcion AS diagnostico,
    hc.fecha
FROM pacientes p
JOIN historias_clinicas hc ON p.id_paciente = hc.id_paciente
LEFT JOIN diagnosticos d ON hc.id_historia = d.id_historia;