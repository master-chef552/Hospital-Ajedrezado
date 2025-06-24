<?php
// perfilPaciente.php
session_start();
require_once __DIR__ . '/conexion.php';

// Aseguramos que el paciente esté logueado
$id_usuario = $_SESSION['id_usuario'] ?? 0;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['error'=>'No autenticado']);
    exit;
}

// Unimos usuario + paciente según tu esquema:
// usuario: [id_usuario], nombre, ap_paterno, ap_materno, fecha_nacimiento, correo, telefono :contentReference[oaicite:4]{index=4}
 // paciente: [id_paciente], id_usuario, tipo_seguro, estatura, curp, dni :contentReference[oaicite:5]{index=5}
$sql = "
  SELECT
    u.nombre,
    u.ap_paterno,
    u.ap_materno,
    CONVERT(varchar(10), u.fecha_nacimiento, 23) AS fecha_nacimiento,
    u.telefono,
    u.correo
  FROM usuario u
  INNER JOIN paciente p
    ON u.id_usuario = p.id_usuario
  WHERE u.id_usuario = ?
";
$params = [ $id_usuario ];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(sqlsrv_errors());
    exit;
}

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error'=>'Paciente no encontrado']);
}
