<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/helpers/schema.php';
require_once __DIR__ . '/helpers/professional_modules.php';
require_once __DIR__ . '/google_citas_sync.php';

function calcularEdadDesdeFecha(?string $fechaNacimiento): ?int {
    if (!$fechaNacimiento) {
        return null;
    }

    try {
        $nacimiento = new DateTime($fechaNacimiento);
        $hoy = new DateTime('today');
    } catch (Exception $e) {
        return null;
    }

    if ($nacimiento > $hoy) {
        return null;
    }

    return $nacimiento->diff($hoy)->y;
}

function normalizarTelefonoPaciente(string $telefono): string {
    return preg_replace('/\D+/', '', $telefono) ?? '';
}

function buscarPacienteExistenteSolicitud(PDO $pdo, string $nombre, string $telefono): ?array {
    $telefonoNormalizado = normalizarTelefonoPaciente($telefono);

    if ($telefonoNormalizado !== '') {
        $stmt = $pdo->prepare("SELECT *
            FROM pacientes
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telefono,''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') = ?
            ORDER BY id ASC
            LIMIT 1");
        $stmt->execute([$telefonoNormalizado]);
        $paciente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($paciente) {
            return $paciente;
        }
    }

    $stmt = $pdo->prepare('SELECT * FROM pacientes WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) ORDER BY id ASC LIMIT 1');
    $stmt->execute([$nombre]);
    $paciente = $stmt->fetch(PDO::FETCH_ASSOC);

    return $paciente ?: null;
}

function resolverPacienteSolicitud(PDO $pdo, string $nombre, ?string $sexo, int $edad, ?string $fechaNacimiento, string $telefono): int {
    $existente = buscarPacienteExistenteSolicitud($pdo, $nombre, $telefono);

    if ($existente) {
        $sexoActual = trim((string)($existente['sexo'] ?? ''));
        $edadActual = isset($existente['edad']) ? (int)$existente['edad'] : 0;
        $fechaActual = trim((string)($existente['fecha_nacimiento'] ?? ''));
        $telefonoActual = trim((string)($existente['telefono'] ?? ''));

        $nuevoSexo = $sexoActual !== '' ? $sexoActual : (string)$sexo;
        $nuevaEdad = $edadActual > 0 ? $edadActual : $edad;
        $nuevaFecha = $fechaActual !== '' ? $fechaActual : (string)$fechaNacimiento;
        $nuevoTelefono = $telefonoActual !== '' ? $telefonoActual : $telefono;

        $stmt = $pdo->prepare('UPDATE pacientes SET sexo = ?, edad = ?, fecha_nacimiento = ?, telefono = ? WHERE id = ?');
        $stmt->execute([
            $nuevoSexo !== '' ? $nuevoSexo : null,
            $nuevaEdad > 0 ? $nuevaEdad : null,
            $nuevaFecha !== '' ? $nuevaFecha : null,
            $nuevoTelefono !== '' ? $nuevoTelefono : null,
            (int)$existente['id'],
        ]);

        return (int)$existente['id'];
    }

    $stmt = $pdo->prepare('INSERT INTO pacientes (nombre, sexo, edad, fecha_nacimiento, telefono) VALUES (?,?,?,?,?)');
    $stmt->execute([$nombre, $sexo, $edad, $fechaNacimiento, $telefono]);
    return (int)$pdo->lastInsertId();
}

function validarChoqueCita(PDO $pdo, string $doctorValor, ?string $fecha, ?string $hora, int $ignorarId = 0): bool {
    $doctorField = obtenerCampoDoctorCitas($pdo);
    if (!$doctorField) {
        return false;
    }

    $sql = "SELECT COUNT(*) FROM citas WHERE {$doctorField} = ? AND fecha = ? AND hora = ? AND LOWER(estado) <> 'cancelada'";
    $params = [$doctorValor, $fecha, $hora];

    if ($ignorarId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $ignorarId;
    }

    $check = $pdo->prepare($sql);
    $check->execute($params);

    return (int)$check->fetchColumn() > 0;
}

function insertarCita(PDO $pdo, int $pacienteId, string $doctorValor, ?string $fecha, ?string $hora, string $motivo, string $estado): int {
    $doctorField = obtenerCampoDoctorCitas($pdo);
    if (!$doctorField) {
        throw new RuntimeException('No existe una columna válida para relacionar doctor y cita.');
    }

    $sql = "INSERT INTO citas (paciente_id, {$doctorField}, fecha, hora, motivo_consulta, estado) VALUES (?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pacienteId, $doctorValor, $fecha, $hora, $motivo, citasEstadoNormalizado($estado)]);
    return (int)$pdo->lastInsertId();
}

function asignarTratamientoACita(PDO $pdo, int $citaId, int $pacienteId, int $tratamientoId, int $cantidad, string $estado = 'planeado', ?string $notas = null): void {
    ensureProfessionalModules($pdo);

    $stmt = $pdo->prepare('SELECT id, precio_base FROM tratamientos_catalogo WHERE id = ? AND COALESCE(activo,1)=1 LIMIT 1');
    $stmt->execute([$tratamientoId]);
    $tratamiento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tratamiento) {
        throw new RuntimeException('Tratamiento inválido.');
    }

    $cantidad = max(1, $cantidad);
    $precioUnitario = (float)$tratamiento['precio_base'];
    $subtotal = $precioUnitario * $cantidad;

    $stmt = $pdo->prepare('INSERT INTO cita_tratamientos (cita_id, tratamiento_id, paciente_id, cantidad, precio_unitario, subtotal, estado, notas) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->execute([$citaId, $tratamientoId, $pacienteId > 0 ? $pacienteId : null, $cantidad, $precioUnitario, $subtotal, $estado, $notas]);
}

function buscarTratamientoActivoPorNombre(PDO $pdo, string $nombre): ?array {
    ensureProfessionalModules($pdo);
    $nombre = trim($nombre);
    if ($nombre === '') {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, nombre, precio_base FROM tratamientos_catalogo WHERE COALESCE(activo,1)=1 AND LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1');
    $stmt->execute([$nombre]);
    $tratamiento = $stmt->fetch(PDO::FETCH_ASSOC);

    return $tratamiento ?: null;
}

function sincronizarMotivoConTratamientoPrincipal(PDO $pdo, int $citaId, ?string $motivoFallback = null): void {
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare("SELECT tc.nombre
                           FROM cita_tratamientos ct
                           JOIN tratamientos_catalogo tc ON tc.id = ct.tratamiento_id
                           WHERE ct.cita_id = ?
                           ORDER BY ct.id ASC
                           LIMIT 1");
    $stmt->execute([$citaId]);
    $motivo = $stmt->fetchColumn();

    if ($motivo === false) {
        $motivo = trim((string)$motivoFallback);
    }

    $stmt = $pdo->prepare('UPDATE citas SET motivo_consulta = ? WHERE id = ?');
    $stmt->execute([trim((string)$motivo), $citaId]);
}

function actualizarTratamientoDeCita(PDO $pdo, int $detalleId, int $cantidad, string $estado = 'planeado', ?string $notas = null): void {
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare('SELECT precio_unitario FROM cita_tratamientos WHERE id = ? LIMIT 1');
    $stmt->execute([$detalleId]);
    $precioUnitario = $stmt->fetchColumn();
    if ($precioUnitario === false) {
        throw new RuntimeException('Tratamiento no encontrado.');
    }

    $cantidad = max(1, $cantidad);
    $subtotal = ((float)$precioUnitario) * $cantidad;
    $stmt = $pdo->prepare('UPDATE cita_tratamientos SET cantidad = ?, subtotal = ?, estado = ?, notas = ? WHERE id = ?');
    $stmt->execute([$cantidad, $subtotal, $estado, $notas, $detalleId]);
}

function eliminarTratamientoDeCita(PDO $pdo, int $detalleId): void {
    ensureProfessionalModules($pdo);
    $stmt = $pdo->prepare('DELETE FROM cita_tratamientos WHERE id = ?');
    $stmt->execute([$detalleId]);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'solicitar') {
    $nombre = trim($_POST['nombre'] ?? '');
    $sexo = $_POST['sexo'] ?? null;
    $edad = (int)($_POST['edad'] ?? 0);
    $fechaNacimiento = $_POST['fecha_nacimiento'] ?: null;
    $telefono = trim($_POST['telefono'] ?? '');
    $fechaPreferida = $_POST['fecha_preferida'] ?: null;
    $horaPreferida = $_POST['hora_preferida'] ?: null;
    $motivo = trim($_POST['motivo'] ?? '');

    if ($nombre === '' || !$sexo || $edad < 0 || !$fechaNacimiento || $telefono === '' || !$fechaPreferida || !$horaPreferida || $motivo === '') {
        redirectPublic('agendar-cita.php?error=1');
    }

    $hoy = new DateTime('today');
    $fechaPreferidaObj = DateTime::createFromFormat('Y-m-d', $fechaPreferida);
    if (!$fechaPreferidaObj || $fechaPreferidaObj->format('Y-m-d') !== $fechaPreferida || $fechaPreferidaObj < $hoy) {
        redirectPublic('agendar-cita.php?error=fecha_pasada');
    }

    $edadCalculada = calcularEdadDesdeFecha($fechaNacimiento);
    if ($edadCalculada === null) {
        redirectPublic('agendar-cita.php?error=fecha_nacimiento');
    }

    if ($edadCalculada !== $edad) {
        redirectPublic('agendar-cita.php?error=edad_incorrecta');
    }

    $pacienteId = resolverPacienteSolicitud($pdo, $nombre, $sexo, $edad, $fechaNacimiento, $telefono);

    $doctorValor = obtenerDoctorPorDefecto($pdo);
    if (!$doctorValor) {
        redirectPublic('agendar-cita.php?error=sin_doctor');
    }

    insertarCita($pdo, $pacienteId, $doctorValor, $fechaPreferida, $horaPreferida, $motivo, 'pendiente');
    redirectPublic('solicitud-enviada.php');
}

require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);
ensureProfessionalModules($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'actualizar') {
    $citaId = (int)($_POST['cita_id'] ?? 0);
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);
    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;
    $redirectTo = trim((string)($_POST['redirect_to'] ?? ''));
    $redirectError = $redirectTo !== '' ? $redirectTo . (str_contains($redirectTo, '?') ? '&' : '?') : 'citas_paciente.php?paciente_id=' . $pacienteId . '&';

    if (!$citaId || !$pacienteId || !$fecha || !$hora) {
        redirectPublic($redirectError . 'error=datos');
    }

    $doctorField = obtenerCampoDoctorCitas($pdo);
    if (!$doctorField) {
        redirectPublic($redirectError . 'error=doctor');
    }

    $stmt = $pdo->prepare("SELECT {$doctorField} AS doctor_valor FROM citas WHERE id = ? LIMIT 1");
    $stmt->execute([$citaId]);
    $citaActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$citaActual || empty($citaActual['doctor_valor'])) {
        redirectPublic($redirectError . 'error=doctor');
    }

    $doctorValor = (string)$citaActual['doctor_valor'];
    if (validarChoqueCita($pdo, $doctorValor, $fecha, $hora, $citaId)) {
        redirectPublic($redirectError . 'choque=1');
    }

    $stmt = $pdo->prepare('UPDATE citas SET fecha = ?, hora = ? WHERE id = ?');
    $stmt->execute([$fecha, $hora, $citaId]);

    try {
        sincronizarCitaConGoogle($pdo, $citaId);
    } catch (Throwable $e) {
        error_log('Error al sincronizar cita actualizada con Google Calendar: ' . $e->getMessage());
    }

    if ($redirectTo !== '') {
        redirectPublic($redirectTo . (str_contains($redirectTo, '?') ? '&' : '?') . 'actualizada=1');
    }

    redirectPublic('citas_paciente.php?paciente_id=' . $pacienteId . '&actualizada=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'estado') {
    $citaId = (int)($_POST['cita_id'] ?? 0);
    $estado = citasEstadoNormalizado($_POST['estado'] ?? 'pendiente');
    $redirectTo = trim((string)($_POST['redirect_to'] ?? ''));

    if ($citaId <= 0 || !in_array($estado, ['confirmada', 'cancelada', 'atendida', 'pendiente'], true)) {
        redirectPublic($redirectTo !== '' ? $redirectTo : 'citas.php?error=estado');
    }

    $stmt = $pdo->prepare('UPDATE citas SET estado = ? WHERE id = ?');
    $stmt->execute([$estado, $citaId]);

    try {
        sincronizarCitaConGoogle($pdo, $citaId);
    } catch (Throwable $e) {
        error_log('Error al sincronizar cambio de estado con Google Calendar: ' . $e->getMessage());
    }

    redirectPublic($redirectTo !== '' ? $redirectTo : 'citas.php?ok=estado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'asignar_tratamiento') {
    $citaId = (int)($_POST['cita_id'] ?? 0);
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);
    $tratamientoId = (int)($_POST['tratamiento_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    $estadoTratamiento = trim($_POST['estado_tratamiento'] ?? 'planeado');
    $notas = trim($_POST['notas_tratamiento'] ?? '');

    if ($citaId <= 0 || $tratamientoId <= 0) {
        redirectPublic('citas.php?error=tratamiento');
    }

    try {
        asignarTratamientoACita($pdo, $citaId, $pacienteId, $tratamientoId, $cantidad, $estadoTratamiento !== '' ? $estadoTratamiento : 'planeado', $notas !== '' ? $notas : null);
        sincronizarMotivoConTratamientoPrincipal($pdo, $citaId);

        try {
            sincronizarCitaConGoogle($pdo, $citaId);
        } catch (Throwable $e) {
            error_log('Error al sincronizar tratamiento asignado con Google Calendar: ' . $e->getMessage());
        }

        redirectPublic('citas.php?ok=tratamiento');
    } catch (Throwable $e) {
        redirectPublic('citas.php?error=tratamiento');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'editar_tratamiento') {
    $detalleId = (int)($_POST['detalle_id'] ?? 0);
    $cantidad = (int)($_POST['cantidad'] ?? 1);
    $estadoTratamiento = trim($_POST['estado_tratamiento'] ?? 'planeado');
    $notas = trim($_POST['notas_tratamiento'] ?? '');

    if ($detalleId <= 0) {
        redirectPublic('citas.php?error=tratamiento');
    }

    try {
        actualizarTratamientoDeCita($pdo, $detalleId, $cantidad, $estadoTratamiento !== '' ? $estadoTratamiento : 'planeado', $notas !== '' ? $notas : null);
        redirectPublic('citas.php?ok=tratamiento_editado');
    } catch (Throwable $e) {
        redirectPublic('citas.php?error=tratamiento');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'eliminar_tratamiento') {
    $detalleId = (int)($_POST['detalle_id'] ?? 0);
    if ($detalleId <= 0) {
        redirectPublic('citas.php?error=tratamiento');
    }

    try {
        $stmt = $pdo->prepare('SELECT cita_id, motivo_consulta FROM cita_tratamientos ct LEFT JOIN citas c ON c.id = ct.cita_id WHERE ct.id = ? LIMIT 1');
        $stmt->execute([$detalleId]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        eliminarTratamientoDeCita($pdo, $detalleId);

        if (!empty($detalle['cita_id'])) {
            $citaId = (int)$detalle['cita_id'];
            sincronizarMotivoConTratamientoPrincipal($pdo, $citaId, (string)($detalle['motivo_consulta'] ?? ''));

            try {
                sincronizarCitaConGoogle($pdo, $citaId);
            } catch (Throwable $e) {
                error_log('Error al sincronizar eliminación de tratamiento con Google Calendar: ' . $e->getMessage());
            }
        }

        redirectPublic('citas.php?ok=tratamiento_eliminado');
    } catch (Throwable $e) {
        redirectPublic('citas.php?error=tratamiento');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctorValor = trim((string)($_POST['doctor_valor'] ?? $_POST['cedula_doctor'] ?? ''));
    if ($doctorValor === '') {
        $doctorValor = (string)(obtenerDoctorPorDefecto($pdo) ?? '');
    }

    $fecha = $_POST['fecha'] ?: null;
    $hora = $_POST['hora'] ?: null;
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);

    if ($doctorValor === '') {
        redirectPublic('citas.php?error=doctor');
    }

    if (validarChoqueCita($pdo, $doctorValor, $fecha, $hora)) {
        redirectPublic('citas.php?choque=1');
    }

    $motivoConsulta = trim($_POST['motivo_consulta'] ?? '');
    $notasCita = trim($_POST['notas_tratamiento'] ?? '');

    $citaId = insertarCita(
        $pdo,
        $pacienteId,
        $doctorValor,
        $fecha,
        $hora,
        $motivoConsulta,
        (string)($_POST['estado'] ?? 'pendiente')
    );

    $tratamientoPorMotivo = buscarTratamientoActivoPorNombre($pdo, $motivoConsulta);
    if ($tratamientoPorMotivo) {
        try {
            asignarTratamientoACita($pdo, $citaId, $pacienteId, (int)$tratamientoPorMotivo['id'], 1, 'planeado', $notasCita !== '' ? $notasCita : null);
            sincronizarMotivoConTratamientoPrincipal($pdo, $citaId, $motivoConsulta);
        } catch (Throwable $e) {
        }
    }

    try {
        sincronizarCitaConGoogle($pdo, $citaId);
    } catch (Throwable $e) {
        error_log('Error al sincronizar nueva cita con Google Calendar: ' . $e->getMessage());
    }

    redirectPublic('citas.php?ok=1');
}

http_response_code(405);
echo 'Método no permitido';
?>
