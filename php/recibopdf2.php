<!-- Este es el recibo en pdf -->
<?php
require_once '../libreria/dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
try{
  $id_paciente = $_POST["id_paciente"] ?? '';
  $id = $_POST['id'] ?? '';
$folio = $_POST['folio'] ?? '';
$doctor = $_POST['doctor'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$especialidad = $_POST['especialidad'] ?? '';
$css = file_get_contents('../css/reportePDF.css');

require_once __DIR__ . "/conexion.php"; 

//recuperamos el nombre del paciente y el consultorio
$sql = "SELECT  
  CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) AS nombre_completo,
  con.numero_consultorio AS consultorio
FROM paciente p
INNER JOIN cita c ON p.id_paciente = c.id_paciente
INNER JOIN usuario u ON u.id_usuario = p.id_usuario
INNER JOIN asignacion_consultorio asi ON asi.cedula = c.cedula
INNER JOIN consultorio con ON con.id_consultorio = asi.id_consultorio
WHERE p.id_paciente = ? AND c.id_cita = ?";

$params = [$id_paciente, $id];

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
  throw new Exception("Error en consulta: " . print_r(sqlsrv_errors(), true));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
  throw new Exception("No se encontró información del paciente o la cita.");
}


$nombre_completo = $row['nombre_completo'];
$consultorio = $row['consultorio'];


ob_start();

?>


<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comprobante Pago</title>
</head>
<body>
  <h1>Comprobante de Pago</h1>
  <p>Gracias por su pago. A continuación se detallan los datos de su transacción:</p>

  <table>
    <tr>
      <th>ID</th>
      <th>Folio</th>
      <th>Nombre del paciente</th>
      <th>Doctor</th>
      <th>Especialidad</th>
      <th>Consultorio</th>
      <th>Fecha</th>
      <th>Hora</th>
    </tr>
    <tr>
      <td><?php echo htmlspecialchars($id); ?></td>
      <td><?php echo htmlspecialchars($folio); ?></td>
      <td><?php echo htmlspecialchars($nombre_completo); ?></td>
      <td><?php echo htmlspecialchars($doctor); ?></td>
      <td><?php echo htmlspecialchars($especialidad); ?></td>
      <td><?php echo htmlspecialchars($consultorio); ?></td>
      <td><?php echo htmlspecialchars($fecha); ?></td>
      <td><?php echo htmlspecialchars($hora); ?></td>
    </tr>
  </table>

</body>


<footer>
  
    <p>Si tiene alguna pregunta, no dude en contactarnos.</p>
    <p>&copy; 2023 Hospital Ajedrezado. Todos los derechos reservados.</p>
  
</footer>

</html>


<?php
$html = ob_get_clean(); // Obtener el contenido del búfer y limpiar el búfer



$dompdf = new Dompdf();

$html = '<style>' . $css . '</style>' . $html; // Agregar el CSS al HTML

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'horizontal'); 
$dompdf->render();
$dompdf->stream("comprobante_de_pago.pdf", array("Attachment" => true));

} catch (Exception $e) {
    echo "Error al procesar los datos: " . $e->getMessage();
    exit;
}

?>