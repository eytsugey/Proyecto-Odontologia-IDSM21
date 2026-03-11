USE odontologia;

CREATE TABLE IF NOT EXISTS odontograma_paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    diente VARCHAR(10) NOT NULL,
    estado VARCHAR(50) NOT NULL,
    descripcion TEXT NULL,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_paciente_diente (paciente_id, diente),
    CONSTRAINT fk_odontograma_paciente
        FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
        ON DELETE CASCADE
);

INSERT INTO odontograma_paciente (paciente_id, diente, estado, descripcion)
VALUES
(1, '16', 'caries', 'Lesión oclusal'),
(1, '26', 'restaurado', 'Resina en buen estado'),
(2, '11', 'fracturado', 'Borde incisal dañado')
ON DUPLICATE KEY UPDATE
estado = VALUES(estado),
descripcion = VALUES(descripcion);