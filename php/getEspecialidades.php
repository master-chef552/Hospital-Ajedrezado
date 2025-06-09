<?php
// inicio de php getEspecialidades
session_start();
if (!isset($_SESSION['id_paciente'])) {
  http_response_code(401);
  echo json_encode(["error" => "No autenticado"]);
  exit();
}

require_once __DIR__ . "/../conexion.php";

$sql = "SELECT id_especialidad, nombre FROM especialidad ORDER BY nombre";
$stmt = sqlsrv_query($conn, $sql);

$especialidades = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $especialidades[] = [
    "id_especialidad" => $row["id_especialidad"],
    "nombre" => $row["nombre"]
  ];
}
sqlsrv_free_stmt($stmt);

header('Content-Type: application/json');
echo json_encode($especialidades);
// fin de php getEspecialidades
?>
