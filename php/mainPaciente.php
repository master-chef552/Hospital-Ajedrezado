<?php
// mainPaciente.php — endpoints para AJAX y procesar formulario
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
session_start();

// 1) Incluir el archivo de conexión, de modo que $conn sea un recurso SQLSRV.
require_once __DIR__ . "/conexion.php"; 

$action = $_GET['action'] ?? '';

// --- Endpoint: Lista de especialidades ---
if ($action === 'getEspecialidades') {
    header('Content-Type: application/json; charset=UTF-8');
    $tsql = "SELECT id_especialidad, nombre FROM especialidad ORDER BY nombre";
    $stmt = sqlsrv_query($conn, $tsql);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// --- Endpoint: Doctores por especialidad ---
if ($action === 'getDoctores') {
    header('Content-Type: application/json; charset=UTF-8');
    $id_esp = intval($_GET['id_especialidad'] ?? 0);
    if (!$id_esp) { echo json_encode([]); exit; }
    $tsql = "
        SELECT m.id_medico,
               e.nombre + ' ' + e.apellidos AS nombre
        FROM medico m
        JOIN empleado e ON m.id_empleado = e.id_empleado
        WHERE m.id_especialidad = ?
        ORDER BY e.nombre
    ";
    $stmt = sqlsrv_query($conn, $tsql, [$id_esp]);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// --- Endpoint: Citas del paciente en sesión ---
if ($action === 'getCitas') {
    header('Content-Type: application/json; charset=UTF-8');
    $id_pac = $_SESSION['id_paciente'] ?? 0;
    if (!$id_pac) { echo json_encode([]); exit; }
    $tsql = "
        SELECT t.id_turno,
               CONVERT(varchar(10), t.fecha_turno, 23) AS fecha,
               CONVERT(varchar(5), t.fecha_turno, 108) AS hora,
               e.nombre + ' ' + e.apellidos AS doctor,
               es.nombre AS estatus
        FROM turno t
        JOIN turno_paciente_medico tpm ON t.id_turno = tpm.id_turno
        JOIN medico m ON tpm.id_medico = m.id_medico
        JOIN empleado e ON m.id_empleado = e.id_empleado
        JOIN estado es ON t.id_estado = es.id_estado
        WHERE tpm.id_paciente = ?
        ORDER BY t.fecha_turno DESC
    ";
    $stmt = sqlsrv_query($conn, $tsql, [$id_pac]);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// --- Procesar formulario de agendar cita ---
if ($action === 'agendarCita' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pac = $_SESSION['id_paciente'] ?? 0;
    $id_med = intval($_POST['id_medico'] ?? 0);
    $fecha  = $_POST['fecha_cita'] ?? '';
    $hora   = $_POST['hora_cita'] ?? '';
    if (!$id_pac || !$id_med || !$fecha || !$hora) {
        header('Location: mainPaciente.html?error=1'); exit;
    }
    // Construir datetime y validar rango y horario
    $dt = "$fecha $hora:00";
    $ts = strtotime($dt);
    if ($ts < time() + 48*3600 || $ts > strtotime('+3 months')) {
        header('Location: mainPaciente.html?error=2'); exit;
    }
    $h = intval(date('H', $ts));
    if ($h < 8 || $h > 18) {
        header('Location: mainPaciente.html?error=3'); exit;
    }
    // Verificar cita duplicada paciente→doctor
    $check = sqlsrv_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM turno_paciente_medico tpm
        JOIN turno t ON tpm.id_turno = t.id_turno
        WHERE tpm.id_paciente = ? AND tpm.id_medico = ? AND t.id_estado IN (1,2)
    ", [$id_pac, $id_med]);
    $cnt = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)['cnt'];
    if ($cnt > 0) {
        header('Location: mainPaciente.html?error=4'); exit;
    }
    // Verificar disponibilidad del doctor
    $check2 = sqlsrv_query($conn, "
        SELECT COUNT(*) AS cnt
        FROM turno t
        JOIN turno_paciente_medico tpm ON t.id_turno = tpm.id_turno
        WHERE tpm.id_medico = ? AND t.fecha_turno = ?
    ", [$id_med, $dt]);
    $cnt2 = sqlsrv_fetch_array($check2, SQLSRV_FETCH_ASSOC)['cnt'];
    if ($cnt2 > 0) {
        header('Location: mainPaciente.html?error=5'); exit;
    }
    // Insertar turno
    sqlsrv_query($conn, 
        "INSERT INTO turno (fecha_turno, id_estado) VALUES (?, 1)",
        [$dt]
    );
    // Obtener nuevo ID
    $res = sqlsrv_query($conn, "SELECT @@IDENTITY AS id");
    $newId = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC)['id'];
    // Insertar relación paciente-medico
    sqlsrv_query($conn,
        "INSERT INTO turno_paciente_medico (id_turno, id_paciente, id_medico) VALUES (?, ?, ?)",
        [$newId, $id_pac, $id_med]
    );
    header('Location: mainPaciente.html');
    exit;
}

// Si no hay acción, simplemente devolvemos 404
http_response_code(404);
exit;
