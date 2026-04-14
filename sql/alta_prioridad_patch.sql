-- Parche opcional para soportar el rol admin sin rehacer toda la base.
-- Ejecuta estas sentencias sobre la BD que estés usando (odontologia u odontologia_db).

ALTER TABLE usuarios
MODIFY rol ENUM('admin','doctor','secretaria','administrador') NOT NULL;

-- Usuario administrador demo
-- contraseña: Admin1234*
INSERT INTO usuarios (nombre, correo, password, rol)
SELECT 'Administrador Demo', 'admin@demo.com', '$2y$10$1AL8gPL1IT8sRP/6fN1R4OdKX0Q3i6l5fZVDFLz2Zg2be7FIFQ7Vm', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM usuarios WHERE correo = 'admin@demo.com'
);
