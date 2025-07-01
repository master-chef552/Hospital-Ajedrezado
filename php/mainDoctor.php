<?php

$action = $_GET['action'] ?? '';


// citasDoctor.php
session_start();
require_once __DIR__ . '/conexion.php';


// solicitamos la cancelación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancelarCita') {
    $id_cita = $_POST['id_cita'] ?? null;

    if (!$id_cita) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el parámetro id_cita"]);
        exit;
    }

    $sql = "UPDATE cita SET id_estado_cita = 8 WHERE id_cita = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id_cita]);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error al cancelar la cita", "details" => sqlsrv_errors()]);
        exit;
    }

    echo json_encode(["message" => "Cita cancelada exitosamente"]);
    header('Location: ../html/mainDoctorhtml.php');
    exit;
}



//obtenemos los datos de la cita seleccionada
if ($action === 'getInfoCita') {
    $id_cita = $_GET['id_cita'] ?? null;
    if (!$id_cita) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el parámetro id_cita"]);
        exit;
    }

    $sql = "SELECT 
		CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) AS nombrePaciente,
		c.id_paciente,
		c.cedula,
		c.folio,
		c.fecha_cita,
		con.numero_consultorio
		FROM cita c
		INNER JOIN asignacion_consultorio asi ON asi.cedula = c.cedula 
		INNER JOIN consultorio con ON con.id_consultorio = asi.id_consultorio
		INNER JOIN paciente p ON p.id_paciente = c.id_paciente
		INNER JOIN usuario u ON u.id_usuario = p.id_usuario
		WHERE c.id_cita = ?";

    $stmt = sqlsrv_query($conn, $sql, [$id_cita]);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener datos de la cita", "details" => sqlsrv_errors()]);
        exit;
    }

    $cita = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if (!$cita) {
    http_response_code(404);
    echo json_encode(["error" => "No se encontró la cita con id: $id_cita"]);
    exit;
}

    if ($cita && isset($cita['fecha_cita'])) {
    $cita['fecha_cita'] = $cita['fecha_cita']->format('Y-m-d');
}

    echo json_encode($cita);
    exit;
}


// Obtener historial del paciente
if ($action == 'getHistorialPaciente') {
    $id_paciente = $_GET['id_paciente'] ?? null;
    if (!$id_paciente) {
        http_response_code(400);
        echo json_encode(["error" => "Falta el parámetro id_paciente"]);
        exit;
    }

    $sql = "SELECT
        b.nombre_paciente,
        re.diagnostico,
        b.nombre_doctor,
        b.especialidad,
        b.consultorio,
        b.fecha_movimiento
        FROM bitacora b 
        INNER JOIN receta re ON re.id_receta = b.id_receta
        WHERE b.id_paciente = ?";

    $stmt = sqlsrv_query($conn, $sql, [$id_paciente]);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error al obtener datos del paciente", "details" => sqlsrv_errors()]);
        exit;
    }

    $historial = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (isset($row['fecha_movimiento']) && $row['fecha_movimiento'] instanceof DateTime) {
            $row['fecha_movimiento'] = $row['fecha_movimiento']->format('Y-m-d');
        }
        $historial[] = $row;
    }

    if (empty($historial)) {
        http_response_code(404);
        echo json_encode(["error" => "No se encontró historial para el paciente con ID: $id_paciente"]);
        exit;
    }

    echo json_encode($historial);
    exit;
}


header('Content-Type: application/json; charset=UTF-8');
// Validar que el usuario esté logueado y sea doctor
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(["error" => "Sesión no iniciada"]);
    exit;
}

// Obtener la cédula del doctor a partir del id_usuario
$sql = "SELECT 
            d.cedula
        FROM doctor d
        INNER JOIN empleado e ON e.id_empleado = d.id_empleado
		INNER JOIN usuario u ON u.id_usuario = e.id_usuario
        WHERE u.id_usuario = ?";

$stmt = sqlsrv_query($conn, $sql, [$id_usuario]);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta de cedula", "details" => sqlsrv_errors()]);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row || !isset($row['cedula'])) {
    http_response_code(404);
    echo json_encode(["error" => "Cedula no encontrada para el usuario", "user_id" => $id_usuario]);
    exit;
}

$cedula = $row['cedula'];

// Consultar citas futuras para esa cédula
$sql = "SELECT 
	    c.id_cita,
		p.id_paciente,
        CONCAT(up.nombre, ' ', up.ap_paterno, ' ', up.ap_materno) AS nombrePaciente,
        c.fecha_cita,
		con.numero_consultorio,
        c.id_estado_cita
    FROM cita c
    INNER JOIN paciente p ON c.id_paciente = p.id_paciente
    INNER JOIN usuario up ON p.id_usuario = up.id_usuario
    INNER JOIN doctor d ON c.cedula = d.cedula
    INNER JOIN empleado e ON d.id_empleado = e.id_empleado
    INNER JOIN usuario ud ON e.id_usuario = ud.id_usuario
    INNER JOIN doctor_especialidad de ON de.cedula = d.cedula
	INNER JOIN asignacion_consultorio asi ON asi.cedula = c.cedula
	INNER JOIN consultorio con ON con.id_consultorio = asi.id_consultorio
    WHERE c.cedula = ?";

$stmt = sqlsrv_query($conn, $sql, [$cedula]);

    
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al consultar citas", "details"=> sqlsrv_errors()]);
    exit;
}



$citas = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $citas[] = $row;
}






echo json_encode($citas);
exit;
?>
