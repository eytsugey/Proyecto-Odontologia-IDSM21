USE odontologia;

DROP TABLE IF EXISTS odontograma_paciente;

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