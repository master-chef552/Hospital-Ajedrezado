<?php
session_start();
require_once __DIR__ . "/conexion.php"; 

$action = $_GET['action'] ?? '';

// --- Obtener especialidades ---
if ($action === 'getEspecialidades') {
    header('Content-Type: application/json; charset=UTF-8');
    $sql = "SELECT id_especialidad, nombre_especialidad AS nombre FROM especialidad ORDER BY nombre";
    $stmt = sqlsrv_query($conn, $sql);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// --- Obtener doctores por especialidad ---
if ($action === 'getDoctores') {
    header('Content-Type: application/json; charset=UTF-8');
    $id_esp = intval($_GET['id_especialidad'] ?? 0);
    if (!$id_esp) { echo json_encode([]); exit; }

    $sql = "
        SELECT 
            u.nombre, 
            u.ap_paterno, 
            u.ap_materno,
            e.nombre_especialidad,
            emp.rfc
        FROM doctor d
        INNER JOIN empleado emp 
            ON d.id_empleado = emp.id_empleado
        INNER JOIN usuario u 
            ON emp.id_usuario = u.id_usuario
        INNER JOIN doctor_especialidad de 
            ON d.cedula = de.cedula
        INNER JOIN especialidad e 
            ON de.id_especialidad = e.id_especialidad
        WHERE e.id_especialidad = ?
        ORDER BY u.nombre, u.ap_paterno;
    ";

    $stmt = sqlsrv_query($conn, $sql, [ $id_esp ]);
    if ($stmt === false) {
        echo json_encode(sqlsrv_errors(), JSON_PRETTY_PRINT);
        exit;
    }

    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}


// --- Obtener citas del paciente ---
if ($action === 'getCitas') {
    header('Content-Type: application/json; charset=UTF-8');
    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    if (!$id_usuario) { echo json_encode([]); exit; }

    $sql = " SELECT up.nombre AS nombre_paciente, up.ap_paterno AS apellido_paciente, ud.nombre AS nombre_doctor,  ud.ap_paterno AS apellido_doctor,
  c.fecha_cita,
  c.fecha_registro,
  c.estatus_cita
FROM cita c
JOIN paciente p ON c.id_paciente = p.id_paciente
JOIN usuario up ON p.id_usuario = up.id_usuario
JOIN doctor d ON c.cedula = d.cedula
JOIN empleado e ON d.id_empleado = e.id_empleado
JOIN usuario ud ON e.id_usuario = ud.id_usuario;
where p.id_usuario = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id_usuario]);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $out[] = $row;
    }
    echo json_encode($out);
    exit;
}

// --- Agendar nueva cita ---
if ($action === 'agendarCita' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['id_usuario'] ?? 0;
    $cedula_doctor = trim($_POST['id_medico'] ?? '');  // aquí viene la cédula del doctor
    $fecha      = $_POST['fecha_cita'] ?? '';
    $hora       = $_POST['hora_cita'] ?? '';
    if (!$id_usuario || !$cedula_doctor || !$fecha || !$hora) {
        header('Location: ../html/mainPaciente.html?error=1');
        exit;
    }

    // 1) Obtener id_paciente desde id_usuario
    $stmt = sqlsrv_query(
        $conn,
        "SELECT id_paciente FROM paciente WHERE id_usuario = ?",
        [ $id_usuario ]
    );
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $id_paciente = $row['id_paciente'] ?? 0;
    if (!$id_paciente) {
        header('Location: ../html/login.html');
        exit;
    }

    // 2) Validar rango de fecha y hora
    $fechaHora = "$fecha $hora:00";
    $ts = strtotime($fechaHora);
    if ($ts < time() + 48 * 3600 || $ts > strtotime('+3 months')) {
        header('Location: ../html/mainPaciente.html?error=2');
        exit;
    }
    $h = intval(date('H', $ts));
    if ($h < 8 || $h > 18) {
        header('Location: ../html/mainPaciente.html?error=3');
        exit;
    }

    // 3) Verificar cita duplicada (paciente–doctor) en la tabla cita
    $check = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS cnt
         FROM cita
         WHERE id_paciente = ? AND cedula = ? AND id_estado_cita IN (1,2)",
        [ $id_paciente, $cedula_doctor ]
    );
    $cnt = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)['cnt'];
    if ($cnt > 0) {
        header('Location: ../html/mainPaciente.html?error=4');
        exit;
    }

    // 4) Verificar disponibilidad del doctor en esa fecha y hora
    $check2 = sqlsrv_query(
        $conn,
        "SELECT COUNT(*) AS cnt
         FROM cita
         WHERE cedula = ? AND fecha_cita = ?",
        [ $cedula_doctor, $fechaHora ]
    );
    $cnt2 = sqlsrv_fetch_array($check2, SQLSRV_FETCH_ASSOC)['cnt'];
    if ($cnt2 > 0) {
        header('Location: ../html/mainPaciente.html?error=5');
        exit;
    }

    // 5) Insertar directamente en la tabla 'cita'
    $fechaRegistro = date('Y-m-d H:i:s');  // formato adecuado para SQL Server
    $sql = "
        INSERT INTO cita
            (cedula, id_paciente, id_pago, fecha_cita, fecha_registro, estatus_cita)
        VALUES (?, ?, 1, ?, ?, 1)
    ";
    $params = [
        $cedula_doctor,
        $id_paciente,
        $fechaHora,
        $fechaRegistro
    ];
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

   header('Location: ../html/mainPaciente.html?success=1');
exit;

}


http_response_code(404);
exit;
