
<?php
//  inicio de php agendarCita
// agendarCita.php

session_start();

// 1) Verificar que el paciente esté logueado
if (!isset($_SESSION['id_paciente'])) {
    header("Location: ../html/login.html");
    exit();
}

$idPaciente = $_SESSION['id_paciente'];

// 2) Incluir la conexión (SQLSRV)
require_once __DIR__ . "/../conexion.php";

// 3) Recoger y sanitizar datos del formulario
$idDoctor  = isset($_POST['id_doctor'])  ? intval($_POST['id_doctor']) : 0;
$fechaStr  = isset($_POST['fecha_cita']) ? trim($_POST['fecha_cita']) : '';
$horaStr   = isset($_POST['hora_cita'])  ? trim($_POST['hora_cita'])  : '';

// Si falta id_doctor → error 1
if ($idDoctor <= 0) {
    header("Location: ../html/mainPaciente.html?error=1");
    exit();
}

// 4) Verificar que fecha y hora no estén vacías
if (empty($fechaStr) || empty($horaStr)) {
    // Podemos tratarlo como “fecha inválida” (error 2)
    header("Location: ../html/mainPaciente.html?error=2");
    exit();
}

// 5) Construir un objeto DateTime con la fecha + hora
//    Formato esperado: fechaStr = 'YYYY-MM-DD', horaStr = 'HH:MM'
$fechaHoraStr = $fechaStr . ' ' . $horaStr . ':00';
try {
    $fechaHora = new DateTime($fechaHoraStr);
} catch (Exception $e) {
    header("Location: ../html/mainPaciente.html?error=2");
    exit();
}

// 6) Validar rango: mínimo 48 hrs y máximo 3 meses
$ahora     = new DateTime();
$limiteMin = (clone $ahora)->add(new DateInterval('PT48H'));          // +48 horas
$limiteMax = (clone $ahora)->add(new DateInterval('P3M'));            // +3 meses

if ($fechaHora < $limiteMin || $fechaHora > $limiteMax) {
    header("Location: ../html/mainPaciente.html?error=2");
    exit();
}

// 7) Verificar que la cita no sea en fecha pasada (ya cubierto por el min, pero reforzamos)
if ($fechaHora < $ahora) {
    header("Location: ../html/mainPaciente.html?error=2");
    exit();
}

// 8) Validar horario laboral del doctor (ejemplo: 08:00–18:00)
//    Si tus doctores tienen horarios en BD, reemplaza este bloque con la consulta correspondiente
$horaMin = new DateTime($fechaStr . ' 08:00:00');
$horaMax = new DateTime($fechaStr . ' 18:00:00');
if ($fechaHora < $horaMin || $fechaHora > $horaMax) {
    header("Location: ../html/mainPaciente.html?error=3");
    exit();
}

// 9) Verificar que el doctor no esté ocupado en esa fecha y hora
$sqlDisp = "
    SELECT COUNT(*) AS cnt
    FROM cita
    WHERE id_doctor = ?
      AND fecha_cita = ?
      AND estatus IN ('Agendada pendiente de pago','Pagada pendiente por atender')
";
$paramsDisp = [ $idDoctor, $fechaHora->format('Y-m-d H:i:s') ];
$stmtDisp = sqlsrv_query($conn, $sqlDisp, $paramsDisp);
if ($stmtDisp === false) {
    die("Error al verificar disponibilidad del doctor: " . print_r(sqlsrv_errors(), true));
}
$rowDisp = sqlsrv_fetch_array($stmtDisp, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmtDisp);
if ($rowDisp['cnt'] > 0) {
    header("Location: ../html/mainPaciente.html?error=5");
    exit();
}

// 10) Verificar que el paciente no tenga una cita pendiente con ese mismo doctor
$sqlPend = "
    SELECT COUNT(*) AS cnt
    FROM cita
    WHERE id_doctor = ?
      AND id_paciente = ?
      AND estatus IN ('Agendada pendiente de pago','Pagada pendiente por atender')
";
$paramsPend = [ $idDoctor, $idPaciente ];
$stmtPend = sqlsrv_query($conn, $sqlPend, $paramsPend);
if ($stmtPend === false) {
    die("Error al verificar cita pendiente del paciente: " . print_r(sqlsrv_errors(), true));
}
$rowPend = sqlsrv_fetch_array($stmtPend, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmtPend);
if ($rowPend['cnt'] > 0) {
    header("Location: ../html/mainPaciente.html?error=4");
    exit();
}

// 11) Preparar la inserción de la nueva cita
// Obtener próximo id_cita
$sqlMax = "SELECT ISNULL(MAX(id_cita), 0) + 1 AS next_id FROM cita";
$stmtMax = sqlsrv_query($conn, $sqlMax);
if ($stmtMax === false) {
    die("Error al obtener siguiente id_cita: " . print_r(sqlsrv_errors(), true));
}
$rowMax = sqlsrv_fetch_array($stmtMax, SQLSRV_FETCH_ASSOC);
$sqlsrv_free_stmt_max = sqlsrv_free_stmt($stmtMax);
$nextIdCita = (int) $rowMax['next_id'];

// 12) Insertar en la tabla cita con estatus inicial
$estatusInicial = "Agendada pendiente de pago";
$sqlInsert = "
    INSERT INTO cita (
      id_cita,
      id_paciente,
      id_doctor,
      fecha_cita,
      estatus
    ) VALUES (
      ?, ?, ?, ?, ?
    )
";
$paramsInsert = [
    $nextIdCita,
    $idPaciente,
    $idDoctor,
    $fechaHora->format('Y-m-d H:i:s'),
    $estatusInicial
];
$stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
if ($stmtInsert === false) {
    die("Error al insertar nueva cita: " . print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmtInsert);

// 13) Todo bien: redirigir a mainPaciente.html (sin parámetros de error)
header("Location: ../html/mainPaciente.html");
exit();

//fin de php agendarCita
?>
