CREATE DATABASE IF NOT EXISTS odontologia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE odontologia;

DROP TABLE IF EXISTS odontograma;
DROP TABLE IF EXISTS historias_clinicas;
DROP TABLE IF EXISTS citas;
DROP TABLE IF EXISTS pacientes;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  correo VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  rol ENUM('doctor','secretaria') NOT NULL
);

CREATE TABLE pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  sexo VARCHAR(20) NULL,
  edad INT NULL,
  fecha_nacimiento DATE NULL,
  telefono VARCHAR(30) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE citas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  doctor_id INT NOT NULL DEFAULT 1,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  motivo_consulta VARCHAR(255) NULL,
  estado ENUM('pendiente','confirmada','atendida','cancelada') NOT NULL DEFAULT 'pendiente',
  CONSTRAINT fk_citas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

CREATE TABLE historias_clinicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL UNIQUE,
  alergias TEXT NULL,
  cirugias TEXT NULL,
  diabetes VARCHAR(100) NULL,
  presion_alta VARCHAR(100) NULL,
  presion_baja VARCHAR(100) NULL,
  medicamentos TEXT NULL,
  habitos TEXT NULL,
  motivo_consulta TEXT NULL,
  CONSTRAINT fk_historia_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

CREATE TABLE odontograma (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  numero_diente INT NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'sano',
  descripcion TEXT NULL,
  UNIQUE KEY uq_paciente_diente (paciente_id, numero_diente),
  CONSTRAINT fk_odontograma_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

INSERT INTO usuarios (nombre, correo, password, rol) VALUES
('Dr. Demo', 'doctor@demo.com', '$2y$12$Z.kzDMEhNZDPqC642B9O8Od/ymZgBa9bAbTvB1l1IxrZ4s93Aln8S', 'doctor'),
('Secretaria Demo', 'secretaria@demo.com', '$2y$12$Z.kzDMEhNZDPqC642B9O8Od/ymZgBa9bAbTvB1l1IxrZ4s93Aln8S', 'secretaria');

INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES
('Juan Pérez', 'Masculino', 30, '1995-05-10', '6561234567'),
('María López', 'Femenino', 28, '1997-09-21', '6569876543');

INSERT INTO citas (paciente_id, doctor_id, fecha, hora, motivo_consulta, estado) VALUES
(1, 1, CURDATE(), '09:00:00', 'Limpieza dental', 'confirmada'),
(2, 1, CURDATE(), '10:00:00', 'Dolor molar', 'pendiente');

INSERT INTO historias_clinicas (paciente_id, alergias, cirugias, diabetes, presion_alta, presion_baja, medicamentos, habitos, motivo_consulta) VALUES
(1, 'Ninguna', 'No', 'No', 'No', 'No', 'Ninguno', 'No fuma', 'Revisión general');

INSERT INTO odontograma (paciente_id, numero_diente, estado, descripcion) VALUES
(1, 8, 'caries', 'Caries superficial'),
(1, 14, 'restaurado', 'Resina previa');

CREATE TABLE odontograma_paciente (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  diente VARCHAR(10) NOT NULL,
  estado VARCHAR(50) NOT NULL DEFAULT 'sano',
  descripcion TEXT NULL,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_odontograma_paciente_diente (paciente_id, diente),
  CONSTRAINT fk_odontograma_paciente_v2
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
    ON DELETE CASCADE
);

INSERT INTO odontograma_paciente (paciente_id, diente, estado, descripcion)
SELECT p.id, d.diente, 'sano', ''
FROM pacientes p
JOIN (
  SELECT '18' AS diente UNION ALL
  SELECT '17' UNION ALL
  SELECT '16' UNION ALL
  SELECT '15' UNION ALL
  SELECT '14' UNION ALL
  SELECT '13' UNION ALL
  SELECT '12' UNION ALL
  SELECT '11' UNION ALL
  SELECT '21' UNION ALL
  SELECT '22' UNION ALL
  SELECT '23' UNION ALL
  SELECT '24' UNION ALL
  SELECT '25' UNION ALL
  SELECT '26' UNION ALL
  SELECT '27' UNION ALL
  SELECT '28' UNION ALL
  SELECT '48' UNION ALL
  SELECT '47' UNION ALL
  SELECT '46' UNION ALL
  SELECT '45' UNION ALL
  SELECT '44' UNION ALL
  SELECT '43' UNION ALL
  SELECT '42' UNION ALL
  SELECT '41' UNION ALL
  SELECT '31' UNION ALL
  SELECT '32' UNION ALL
  SELECT '33' UNION ALL
  SELECT '34' UNION ALL
  SELECT '35' UNION ALL
  SELECT '36' UNION ALL
  SELECT '37' UNION ALL
  SELECT '38'
) d
WHERE p.id IN (1,2,3);

UPDATE odontograma_paciente
SET estado = 'caries', descripcion = 'Lesion oclusal'
WHERE paciente_id = 1 AND diente = '16';

UPDATE odontograma_paciente
SET estado = 'restaurado', descripcion = 'Resina en buen estado'
WHERE paciente_id = 1 AND diente = '26';

UPDATE odontograma_paciente
SET estado = 'fracturado', descripcion = 'Golpe reciente'
WHERE paciente_id = 2 AND diente = '11';

UPDATE odontograma_paciente
SET estado = 'caries', descripcion = 'Pequena caries'
WHERE paciente_id = 2 AND diente = '21';

UPDATE odontograma_paciente
SET estado = 'endodoncia', descripcion = 'Tratamiento realizado'
WHERE paciente_id = 3 AND diente = '46';
