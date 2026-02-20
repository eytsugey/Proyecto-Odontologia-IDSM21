-- CREACIÓN DE BASE DE DATOS
CREATE DATABASE IF NOT EXISTS odontologia_db;
USE odontologia_db;
-- TABLA USUARIOS
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('administrador','secretario','empleado','paciente') NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_rol ON usuarios(rol);

-- TABLA PACIENTES
CREATE TABLE pacientes (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    fecha_nacimiento DATE,
    direccion VARCHAR(200),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
);
-- TABLA CITAS
CREATE TABLE citas (
    id_cita INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    id_dentista INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    estado ENUM('solicitada','confirmada','realizada','cancelada') 
        DEFAULT 'solicitada',
    creada_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE,
    FOREIGN KEY (id_dentista) REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE,
    FOREIGN KEY (creada_por) REFERENCES usuarios(id_usuario)
);

-- Evitar choque de citas mismo dentista, misma fecha y hora
CREATE UNIQUE INDEX idx_cita_unica 
ON citas(id_dentista, fecha, hora);

-- TABLA HISTORIAL CLÍNICO
CREATE TABLE historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT UNIQUE NOT NULL,
    id_paciente INT NOT NULL,
    id_dentista INT NOT NULL,
    diagnostico TEXT,
    observaciones TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cita) REFERENCES citas(id_cita)
        ON DELETE CASCADE,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente),
    FOREIGN KEY (id_dentista) REFERENCES usuarios(id_usuario)
);

-- TABLA DIENTES (ODONTOGRAMA)
CREATE TABLE dientes_historial (
    id_diente INT AUTO_INCREMENT PRIMARY KEY,
    id_historial INT NOT NULL,
    numero_diente INT NOT NULL,
    estado ENUM('sano','caries','restaurado','extraido','fracturado','endodoncia') NOT NULL,
    descripcion TEXT,

    UNIQUE KEY uq_historial_diente (id_historial, numero_diente),

    FOREIGN KEY (id_historial) REFERENCES historial(id_historial)
        ON DELETE CASCADE
);

CREATE INDEX idx_diente_paciente ON dientes_historial(numero_diente);

-- TABLA TRATAMIENTOS
CREATE TABLE tratamientos (
    id_tratamiento INT AUTO_INCREMENT PRIMARY KEY,
    id_paciente INT NOT NULL,
    descripcion TEXT NOT NULL,
    prioridad ENUM('baja','media','alta') DEFAULT 'media',
    estado ENUM('pendiente','en_proceso','finalizado') DEFAULT 'pendiente',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES pacientes(id_paciente)
        ON DELETE CASCADE
);

-- TABLA PAGOS
CREATE TABLE pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT UNIQUE NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    metodo_pago ENUM('efectivo','terminal') NOT NULL,
    fecha_pago DATE NOT NULL,
    FOREIGN KEY (id_cita) REFERENCES citas(id_cita)
        ON DELETE CASCADE
);

CREATE INDEX idx_fecha_pago ON pagos(fecha_pago);


-- =================== VISTAS ==========================
-- 1️⃣ Vista Financiera Semanal (Administrador)
CREATE VIEW vista_finanzas_semanal AS
SELECT 
    YEAR(fecha_pago) AS anio,
    WEEK(fecha_pago) AS semana,
    metodo_pago,
    SUM(monto) AS total_ingresos
FROM pagos
GROUP BY anio, semana, metodo_pago;
-- 2️⃣ Vista Agenda por Dentista
CREATE VIEW vista_agenda_dentista AS
SELECT 
    u.nombre AS dentista,
    p.nombre AS paciente_nombre,
    p.apellido AS paciente_apellido,
    c.fecha,
    c.hora,
    c.estado
FROM citas c
JOIN usuarios u ON c.id_dentista = u.id_usuario
JOIN pacientes p ON c.id_paciente = p.id_paciente;
-- 3️⃣ Vista Odontograma Completo
CREATE VIEW vista_odontograma AS
SELECT 
    p.id_paciente,
    p.nombre,
    p.apellido,
    d.numero_diente,
    d.estado,
    d.descripcion
FROM dientes_historial d
JOIN historial h ON d.id_historial = h.id_historial
JOIN pacientes p ON h.id_paciente = p.id_paciente;
