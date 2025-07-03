<?php

$action = $_POST['action'] ?? ($_GET['action'] ?? '');



session_start();
require_once __DIR__ . '/conexion.php';

if($action == "registrarFalta"){
    $id_cita = $_POST['id_cita'] ?? null;

    if (!$id_cita) {
        echo json_encode(['success' => false, 'error' => 'Falta el ID de la cita.']);
        exit;
    }
    $sql = "UPDATE cita SET id_estado_cita = 7 WHERE id_cita = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id_cita]);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(["error" => "Error al registrar la falta", "details" => sqlsrv_errors()]);
        exit;
    }

    header('Content-Type: application/json');
   echo json_encode(["success" => true, "message" => "Falta registrada exitosamente"]);
exit;

}


//finalizamos la cita

if($action == 'finalizarCita'){
    $id_usuario   = $_POST['id_usuario']   ?? null;
    $id_paciente  = $_POST['id_paciente']  ?? null;
    $id_cita_php  = $_POST['id_cita']      ?? null;  // sólo si lo necesitas aquí
    $diagnostico  = $_POST['diagnostico']  ?? null;
    $nombre_med   = $_POST['nombre_medicamento'] ?? null;
    $dosis        = $_POST['dosis']        ?? null;
    $observaciones= $_POST['observaciones'] ?? null;
    $fechaHora    = date("Y-m-d H:i:s");

    try {
        // 1) Obtengo datos del doctor (igual que antes)
        $sql = "
          SELECT esp.nombre_especialidad,
                 con.numero_consultorio,
                 d.cedula,
                 CONCAT(u.nombre,' ',u.ap_paterno,' ',u.ap_materno) AS nombre_doctor
          FROM usuario u
          JOIN empleado e ON e.id_usuario = u.id_usuario
          JOIN doctor d   ON d.id_empleado = e.id_usuario
          JOIN doctor_especialidad de ON de.cedula = d.cedula
          JOIN especialidad esp        ON esp.id_especialidad = de.id_especialidad
          JOIN asignacion_consultorio asi ON asi.cedula = d.cedula
          JOIN consultorio con ON con.id_consultorio = asi.id_consultorio
          WHERE u.id_usuario = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id_usuario]);
        if (!$stmt) throw new Exception("Error al obtener datos del doctor: ". print_r(sqlsrv_errors(), true));
        $doctor = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$doctor) throw new Exception("Doctor no encontrado.");
        extract($doctor, EXTR_PREFIX_ALL, "doc");
        // $doc_nombre_especialidad, $doc_numero_consultorio, $doc_cedula, $doc_nombre_doctor

        // 2) Obtengo datos del paciente
        $sql = "
          SELECT CONCAT(u.nombre,' ',u.ap_paterno,' ',u.ap_materno) AS nombre_paciente
          FROM usuario u
          JOIN paciente p ON p.id_usuario = u.id_usuario
          WHERE p.id_paciente = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id_paciente]);
        if (!$stmt) throw new Exception("Error al obtener datos del paciente: ". print_r(sqlsrv_errors(), true));
        $pac = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$pac) throw new Exception("Paciente no encontrado.");
        $nombre_paciente = $pac['nombre_paciente'];

        // 3) Llamada al SP para generar receta (ahora devuelve id_receta e id_cita)
        $sql = "EXEC sp_generar_receta ?, ?, ?, ?, ?, ?";
        $params = [
          $id_paciente,          // @id_paciente
          $doc_cedula,           // @id_medico (cédula)
          $diagnostico,          // @diagnostico
          $nombre_med,           // @medicamento
          $dosis,                // @dosis
          $observaciones         // @observaciones
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if (!$stmt) {
            throw new Exception("Error al generar receta: ". print_r(sqlsrv_errors(), true));
        }
        // Leer el SELECT final del SP
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $nuevo_id_receta = $row['id_receta'];
        $id_cita_sp      = $row['id_cita'];

        // 4) Inserto la bitácora usando las dos IDs obtenidas
        // Primero obtengo nuevo id_bitacora
        $sql = "SELECT MAX(id_bitacora) AS max_id FROM bitacora";
        $stmt = sqlsrv_query($conn, $sql);
        if (!$stmt) throw new Exception("Error al consultar bitácora: ". print_r(sqlsrv_errors(), true));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $nuevo_id_bitacora = $row['max_id'] + 1;

        // Inserción definitiva
        $sql = "
          INSERT INTO bitacora
            (id_bitacora, fecha_movimiento, especialidad, nombre_paciente,
             consultorio, cedula, nombre_doctor, id_cita, id_receta, id_paciente)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
          $nuevo_id_bitacora,
          $fechaHora,
          $doc_nombre_especialidad,
          $nombre_paciente,
          $doc_numero_consultorio,
          $doc_cedula,
          $doc_nombre_doctor,
          $id_cita_sp,
          $nuevo_id_receta,
          $id_paciente
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        if (!$stmt) throw new Exception("Error al insertar bitácora: ". print_r(sqlsrv_errors(), true));




        $sql3 = "UPDATE cita SET id_estado_cita = 6 WHERE id_cita = ?";
        $stmt3 = sqlsrv_query($conn, $sql3, [$id_cita_php]);
        if (!$stmt3) {
            throw new Exception("Error al actualizar estado de cita: ". print_r(sqlsrv_errors(), true));
        }
        // 5) Respuesta JSON
        header('Content-Type: application/json');
        echo json_encode([
          "success"       => true,
          "message"       => "Cita finalizada exitosamente",
          "id_cita_used"  => $id_cita_sp,
          "id_receta"     => $nuevo_id_receta
        ]);
        exit;


        

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success"=>false, "error"=>$e->getMessage()]);
        exit;
    }
}


?>
