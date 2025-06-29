<!-- Este es el recibo en pdf -->
<?php
try{
  $id = $_POST['id'] ?? '';
$folio = $_POST['folio'] ?? '';
$doctor = $_POST['doctor'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$hora = $_POST['hora'] ?? '';
$especialidad = $_POST['especialidad'] ?? '';
$css = file_get_contents('../css/reportePDF.css');


// Si lo deseas, aquí puedes consultar base de datos para obtener más datos (como monto, forma_pago, etc.)
ob_start();
} catch (Exception $e) {
    echo "Error al procesar los datos: " . $e->getMessage();
    exit;
}
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
      <th>Doctor</th>
      <th>Especialidad</th>
      <th>Fecha</th>
      <th>Hora</th>
    </tr>
    <tr>
      <td><?php echo htmlspecialchars($id); ?></td>
      <td><?php echo htmlspecialchars($folio); ?></td>
      <td><?php echo htmlspecialchars($doctor); ?></td>
      <td><?php echo htmlspecialchars($especialidad); ?></td>
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


require_once '../libreria/dompdf/autoload.inc.php'; 
use Dompdf\Dompdf;
$dompdf = new Dompdf();

$html = '<style>' . $css . '</style>' . $html; // Agregar el CSS al HTML

$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'horizontal'); // Cambia a 'portrait' si prefieres vertical
$dompdf->render();
$dompdf->stream("comprobante_de_pago.pdf", array("Attachment" => false));

?>