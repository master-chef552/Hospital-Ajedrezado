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

    $sql = "SELECT 
            u.nombre, 
            u.ap_paterno, 
            u.ap_materno,
            e.nombre_especialidad,
            d.cedula
        FROM doctor d
        INNER JOIN empleado emp 
            ON d.id_empleado = emp.id_empleado
        INNER JOIN usuario u 
            ON emp.id_usuario = u.id_usuario
        INNER JOIN doctor_especialidad de 
            ON d.cedula = de.cedula
        INNER JOIN especialidad e 
            ON de.id_especialidad = e.id_especialidad
        WHERE e.id_especialidad = ? AND emp.estatus = 'Activo'
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

    $sql = "SELECT 
        p.id_paciente,
        c.folio,
        c.id_cita,
        c.id_estado_cita,
        ud.nombre AS nombre_doctor,  
        ud.ap_paterno AS apellido_doctor,
        c.fecha_cita,
        ec.estado AS estado_cita,
	    esp.nombre_especialidad AS nombre_especialidad
    FROM cita c
    INNER JOIN paciente p ON c.id_paciente = p.id_paciente
    INNER JOIN usuario up ON p.id_usuario = up.id_usuario
    INNER JOIN doctor d ON c.cedula = d.cedula
    INNER JOIN empleado e ON d.id_empleado = e.id_empleado
    INNER JOIN usuario ud ON e.id_usuario = ud.id_usuario
    INNER JOIN estado_cita ec ON ec.id_estado_cita = c.id_estado_cita
    INNER JOIN doctor_especialidad de ON de.cedula = d.cedula
    INNER JOIN especialidad esp ON esp.id_especialidad = de.id_especialidad
    WHERE p.id_usuario = ?";

    $stmt = sqlsrv_query($conn, $sql, [$id_usuario]);
    $out = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['fecha_cita'] instanceof DateTime) {
        $row['fecha_cita'] = $row['fecha_cita']->format('Y-m-d H:i:s');
    }
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
        header('Location: ../html/mainPacientehtml.php?error=1');
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
        header('Location: ../html/mainPacientehtml.php?error=2');
        exit;
    }
    $h = intval(date('H', $ts));
    if ($h < 8 || $h > 18) {
        header('Location: ../html/mainPacientehtml.php?error=3');
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
        header('Location: ../html/mainPacientehtml.php?error=4');
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
        header('Location: ../html/mainPacientehtml.php?error=5');
        exit;
    }

    try{
        // 5) crear pago
$resPago = sqlsrv_query($conn, "SELECT ISNULL(MAX(id_pago), 0) + 1 AS next_id FROM pago");
$rowPago = sqlsrv_fetch_array($resPago, SQLSRV_FETCH_ASSOC);
$id_pago = (int)$rowPago['next_id']; 

$fechaLimite = date('Y-m-d H:i:s', time() + 48 * 3600); // 48 horas después

$sqlPago = "
    INSERT INTO pago (id_pago, monto, forma_pago, limite_pago, id_estado_pago)
    VALUES (?, '500.00', 'Tarjeta', ?, 2)
";
$paramsPago = [
    $id_pago,
    $fechaLimite
];
$stmtPago = sqlsrv_query($conn, $sqlPago, $paramsPago);

if (!$stmtPago) {
    die("Error al insertar pago: " . print_r(sqlsrv_errors(), true));
}




    // 6) Insertar directamente en la tabla 'cita'
    $fechaRegistro = date('Y-m-d H:i:s');  // formato adecuado para SQL Server
    // Antes del insert:
    $seq = sqlsrv_query($conn, "SELECT ISNULL(MAX(id_cita), 0) + 1 AS next_id FROM cita");
    $row = sqlsrv_fetch_array($seq, SQLSRV_FETCH_ASSOC);
    $nextId = $row['next_id'];
    $folio = $cedula_doctor . '-' . $id_paciente; // Generar folio único

    // Luego en tu INSERT:
    $sql = "
            INSERT INTO cita
            (id_cita, cedula, id_paciente, id_pago, fecha_cita, fecha_registro, estatus_cita, id_estado_cita, folio)
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, ?)
        ";
    $params = [
    $nextId,
    $cedula_doctor,
    $id_paciente,
    $id_pago,  
    $fechaHora,
    $fechaRegistro,
    $folio
];


    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

   header('Location: ../html/mainPacientehtml.php?success=1');
exit;
    }catch (Exception $e) {
        // Si ocurre un error, redirigir a la página principal con un mensaje de error
        header('Location: ../html/mainPacientehtml.php?error=6');
        exit;
    }

}


http_response_code(404);
exit;