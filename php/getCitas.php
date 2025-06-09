<?php
//inicio de php getCitas
// getCitas.php

session_start();

// 1) Verificar que el paciente esté logueado
if (!isset($_SESSION['id_paciente'])) {
    http_response_code(401);
    echo json_encode(["error" => "No autenticado"]);
    exit();
}

$idPaciente = $_SESSION['id_paciente'];

// 2) Incluir la conexión
require_once __DIR__ . "/conexion.php";

// 3) Preparar y ejecutar la consulta
$sql = "
    SELECT 
        c.id_cita,
        d.nombre + ' ' + d.apellidos AS nombre_doctor,
        CONVERT(VARCHAR(10), c.fecha_cita, 120) AS fecha,
        CONVERT(VARCHAR(5), c.fecha_cita, 108) AS hora,
        c.estatus
    FROM cita c
    INNER JOIN doctor d ON c.id_doctor = d.id_doctor
    WHERE c.id_paciente = ?
    ORDER BY c.fecha_cita DESC
";
$params = [ $idPaciente ];
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Error al obtener citas"]);
    exit();
}

// 4) Recorrer resultados en un arreglo
$citas = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $citas[] = [
        "id_cita"      => $row['id_cita'],
        "nombre_doctor"=> $row['nombre_doctor'],
        "fecha"        => $row['fecha'],
        "hora"         => $row['hora'],
        "estatus"      => $row['estatus']
    ];
}
sqlsrv_free_stmt($stmt);

// 5) Devolver JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($citas);
 
//fin de php getCitas
?>