<?php
//inicio de php getDoctores
session_start();
if (!isset($_SESSION['id_paciente'])) {
  http_response_code(401);
  echo json_encode(["error" => "No autenticado"]);
  exit();
}

if (!isset($_GET['id_especialidad'])) {
  http_response_code(400);
  echo json_encode(["error" => "Especialidad requerida"]);
  exit();
}

$idEspecialidad = intval($_GET['id_especialidad']);

require_once __DIR__ . "/../conexion.php";

$sql = "
  SELECT id_doctor, nombre + ' ' + apellidos AS nombre_doctor
  FROM doctor
  WHERE id_especialidad = ?
  ORDER BY nombre, apellidos
";
$stmt = sqlsrv_query($conn, $sql, [ $idEspecialidad ]);

$doctores = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $doctores[] = [
    "id_doctor" => $row["id_doctor"],
    "nombre_doctor" => $row["nombre_doctor"]
  ];
}
sqlsrv_free_stmt($stmt);

header('Content-Type: application/json');
echo json_encode($doctores);

//fin de php getDoctores
?>