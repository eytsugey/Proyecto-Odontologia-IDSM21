DROP DATABASE IF EXISTS odontologia;
CREATE DATABASE odontologia;
USE odontologia;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

------------------------------------------------
-- TABLA USUARIOS
------------------------------------------------

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('doctor','secretaria') NOT NULL
);

INSERT INTO usuarios (nombre,email,password,rol) VALUES
('doctor','doctor@clinica.com','123456','doctor'),
('secretaria','secretaria@clinica.com','123456','secretaria');

------------------------------------------------
-- TABLA PACIENTES
------------------------------------------------

CREATE TABLE pacientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  sexo VARCHAR(10),
  edad INT,
  fecha_nacimiento DATE,
  telefono VARCHAR(20)
);

INSERT INTO pacientes (nombre,sexo,edad,fecha_nacimiento,telefono) VALUES
('Carlos Ramirez','M',35,'1989-04-10','6141112233'),
('Ana Martinez','F',28,'1996-09-18','6142223344'),
('Luis Gonzalez','M',42,'1982-01-22','6143334455');

------------------------------------------------
-- TABLA CITAS
------------------------------------------------

CREATE TABLE citas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT,
  fecha DATE,
  hora TIME,
  motivo_consulta TEXT,
  estado VARCHAR(50),
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

INSERT INTO citas (paciente_id,fecha,hora,motivo_consulta,estado) VALUES
(1,'2026-03-10','10:00:00','Dolor molar','realizada'),
(1,'2026-04-01','11:00:00','Limpieza dental','programada'),
(2,'2026-03-15','09:30:00','Revision general','programada'),
(3,'2026-03-08','14:00:00','Caries','realizada');

------------------------------------------------
-- TABLA HISTORIAS CLINICAS
------------------------------------------------

CREATE TABLE historias_clinicas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT UNIQUE,
  alergias VARCHAR(255),
  cirugias VARCHAR(255),
  diabetes VARCHAR(50),
  presion_alta VARCHAR(50),
  presion_baja VARCHAR(50),
  medicamentos VARCHAR(255),
  habitos VARCHAR(255),
  motivo_consulta TEXT,
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

INSERT INTO historias_clinicas 
(paciente_id,alergias,cirugias,diabetes,presion_alta,presion_baja,medicamentos,habitos,motivo_consulta) VALUES
(1,'Penicilina','Ninguna','No','No','No','Ibuprofeno','Fumar','Dolor en muela'),
(2,'Ninguna','Apendicectomia','No','No','No','Ninguno','Cafe','Revision'),
(3,'Aspirina','Ninguna','Si','Si','No','Metformina','Alcohol','Caries');

------------------------------------------------
-- TABLA ODONTOGRAMA
------------------------------------------------

CREATE TABLE odontograma_paciente (
  id INT AUTO_INCREMENT PRIMARY KEY,
  paciente_id INT NOT NULL,
  diente VARCHAR(10) NOT NULL,
  estado VARCHAR(50) NOT NULL DEFAULT 'sano',
  descripcion TEXT,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_odontograma_paciente_diente (paciente_id,diente),

  CONSTRAINT fk_odontograma_paciente_v2
  FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
  ON DELETE CASCADE
);

------------------------------------------------
-- CREAR 32 DIENTES PARA CADA PACIENTE
------------------------------------------------

INSERT INTO odontograma_paciente (paciente_id,diente,estado,descripcion)
SELECT p.id, d.diente, 'sano',''
FROM pacientes p
JOIN (
SELECT '18' diente UNION ALL
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
) d;

------------------------------------------------
-- EJEMPLOS DE TRATAMIENTOS
------------------------------------------------

UPDATE odontograma_paciente
SET estado='caries',descripcion='Lesion oclusal'
WHERE paciente_id=1 AND diente='16';

UPDATE odontograma_paciente
SET estado='restaurado',descripcion='Resina en buen estado'
WHERE paciente_id=1 AND diente='26';

UPDATE odontograma_paciente
SET estado='fracturado',descripcion='Golpe reciente'
WHERE paciente_id=2 AND diente='11';

UPDATE odontograma_paciente
SET estado='caries',descripcion='Pequena caries'
WHERE paciente_id=2 AND diente='21';

UPDATE odontograma_paciente
SET estado='endodoncia',descripcion='Tratamiento realizado'
WHERE paciente_id=3 AND diente='46';

COMMIT;