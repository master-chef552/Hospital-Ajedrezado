<?php
// citasDoctor.php
session_start();
require_once __DIR__ . '/conexion.php';

// Validar que el usuario esté logueado y sea doctor
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(["error" => "Sesión no iniciada"]);
    exit;
}

// Obtener la cédula del doctor a partir del id_usuario
$sql = "
  SELECT cedula from doctor 
    where id_usuario = ?
";
$stmt = sqlsrv_query($conn, $sql, [$id_usuario]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$cedula = $row['cedula'] ?? null;

if (!$cedula) {
    http_response_code(404);
    echo json_encode(["error" => "Doctor no encontrado"]);
    exit;
}

// Consultar citas futuras para esa cédula
$sql = "SELECT 
	    c.id_cita AS consultaID,
        up.nombre AS nombre_paciente,  
        up.ap_paterno AS apellido_paciente,
        c.fecha_cita AS fecha_cita
    FROM cita c
    JOIN paciente p ON c.id_paciente = p.id_paciente
    JOIN usuario up ON p.id_usuario = up.id_usuario
    JOIN doctor d ON c.cedula = d.cedula
    JOIN empleado e ON d.id_empleado = e.id_empleado
    JOIN usuario ud ON e.id_usuario = ud.id_usuario
    JOIN estado_cita ec ON ec.id_estado_cita = c.estatus_cita
    JOIN doctor_especialidad de ON de.cedula = d.cedula
    WHERE c.cedula = ? 
    AND ec.id_estado_cita = 2";
$stmt = sqlsrv_query($conn, $sql, [$cedula]);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta"]);
    exit;
}

$citas = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $citas[] = $row;
}

header('Content-Type: application/json; charset=UTF-8');
echo json_encode($citas);
exit;
?>
