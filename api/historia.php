<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/middleware/auth.php';
requireLogin(['doctor', 'secretaria']);

function boolPost(string $name): int {
    return isset($_POST[$name]) ? 1 : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pacienteId = (int)($_POST['paciente_id'] ?? 0);

    if ($pacienteId <= 0) {
        header('Location: /Proyecto-Odontologia-IDSM21/public/pacientes.php?error=1');
        exit;
    }

    $exists = $pdo->prepare('SELECT id FROM historias_clinicas WHERE paciente_id = ? LIMIT 1');
    $exists->execute([$pacienteId]);
    $row = $exists->fetch(PDO::FETCH_ASSOC);

    $data = [
        // Antecedentes
        boolPost('alergico_med_comida'),
        trim($_POST['cual_alergia'] ?? ''),
        boolPost('intervencion'),
        trim($_POST['cual_intervencion'] ?? ''),
        boolPost('reac_anestesia'),
        trim($_POST['cual_reaccion'] ?? ''),
        boolPost('coagulacion_sang'),
        boolPost('diabetes'),
        trim($_POST['med_diabetes'] ?? ''),
        boolPost('pres_alta'),
        trim($_POST['med_pres_alta'] ?? ''),
        boolPost('pres_baja'),
        trim($_POST['med_pres_baja'] ?? ''),
        boolPost('enf_venereas'),
        trim($_POST['cuales_enf_ven'] ?? ''),
        boolPost('hepatitis'),
        trim($_POST['tipo_hepatitis'] ?? ''),
        boolPost('asma'),
        boolPost('enfermedad'),
        trim($_POST['cuales_enfermedad'] ?? ''),
        boolPost('drogas'),
        trim($_POST['cuales_drogas'] ?? ''),
        boolPost('toma_med'),
        trim($_POST['para_que_med'] ?? ''),
        boolPost('embarazo'),
        trim($_POST['mes_embarazo'] ?? ''),

        // Hábitos
        boolPost('fuma'),
        boolPost('toma'),
        boolPost('farmaco_dep'),
        trim($_POST['cuales_habitos'] ?? ''),

        // Información general
        trim($_POST['motivo_visita'] ?? ''),
        boolPost('primera_vez'),
        ($_POST['ultima_visita'] ?? '') !== '' ? $_POST['ultima_visita'] : null,
        boolPost('dolor_boca'),
        trim($_POST['tipo'] ?? ''),
        trim($_POST['tipo_dientes'] ?? ''),
        trim($_POST['ta'] ?? ''),
        trim($_POST['pulso'] ?? ''),
        trim($_POST['oximetria'] ?? ''),
        trim($_POST['glucosa'] ?? ''),
        trim($_POST['diagnostico'] ?? ''),
    ];

    if ($row) {
        $sql = '
            UPDATE historias_clinicas SET
                alergico_med_comida = ?,
                cual_alergia = ?,
                intervencion = ?,
                cual_intervencion = ?,
                reac_anestesia = ?,
                cual_reaccion = ?,
                coagulacion_sang = ?,
                diabetes = ?,
                med_diabetes = ?,
                pres_alta = ?,
                med_pres_alta = ?,
                pres_baja = ?,
                med_pres_baja = ?,
                enf_venereas = ?,
                cuales_enf_ven = ?,
                hepatitis = ?,
                tipo_hepatitis = ?,
                asma = ?,
                enfermedad = ?,
                cuales_enfermedad = ?,
                drogas = ?,
                cuales_drogas = ?,
                toma_med = ?,
                para_que_med = ?,
                embarazo = ?,
                mes_embarazo = ?,
                fuma = ?,
                toma = ?,
                farmaco_dep = ?,
                cuales_habitos = ?,
                motivo_visita = ?,
                primera_vez = ?,
                ultima_visita = ?,
                dolor_boca = ?,
                tipo = ?,
                tipo_dientes = ?,
                ta = ?,
                pulso = ?,
                oximetria = ?,
                glucosa = ?,
                diagnostico = ?
            WHERE paciente_id = ?
        ';
        $data[] = $pacienteId;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    } else {
        $sql = '
            INSERT INTO historias_clinicas (
                alergico_med_comida,
                cual_alergia,
                intervencion,
                cual_intervencion,
                reac_anestesia,
                cual_reaccion,
                coagulacion_sang,
                diabetes,
                med_diabetes,
                pres_alta,
                med_pres_alta,
                pres_baja,
                med_pres_baja,
                enf_venereas,
                cuales_enf_ven,
                hepatitis,
                tipo_hepatitis,
                asma,
                enfermedad,
                cuales_enfermedad,
                drogas,
                cuales_drogas,
                toma_med,
                para_que_med,
                embarazo,
                mes_embarazo,
                fuma,
                toma,
                farmaco_dep,
                cuales_habitos,
                motivo_visita,
                primera_vez,
                ultima_visita,
                dolor_boca,
                tipo,
                tipo_dientes,
                ta,
                pulso,
                oximetria,
                glucosa,
                diagnostico,
                paciente_id
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ';
        $data[] = $pacienteId;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    }

    header('Location: /Proyecto-Odontologia-IDSM21/public/historia.php?paciente_id=' . $pacienteId . '&ok=1');
    exit;
}

http_response_code(405);
echo 'Método no permitido';
?>