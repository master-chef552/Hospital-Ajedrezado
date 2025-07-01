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
    $id_usuario = $_POST['id_usuario'] ?? null;
    $id_paciente = $_POST['id_paciente'] ?? null;
    $id_cita = $_POST['id_cita'] ?? null;
    $diagnostico = $_POST['diagnostico'] ?? null;
    $nombre_medicamento = $_POST['nombre_medicamento'] ?? null;
    $dosis = $_POST['dosis'] ?? null;
    $fechaHora = date("Y-m-d H:i:s"); 
    $observaciones = $_POST['observaciones'] ?? null;
    
    try{
        //obtenemos los datos que nos faltan del doctor
        $sql = "SELECT
        esp.nombre_especialidad,
        con.numero_consultorio,
        d.cedula,
        CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) AS nombre_doctor
        FROM usuario u
        INNER JOIN empleado e ON e.id_usuario = u.id_usuario
        INNER JOIN doctor d ON d.id_empleado = e.id_usuario
        INNER JOIN doctor_especialidad de ON de.cedula = d.cedula
        INNER JOIN especialidad esp ON esp.id_especialidad = de.id_especialidad
        INNER JOIN asignacion_consultorio asi ON asi.cedula = d.cedula
        INNER JOIN consultorio con ON con.id_consultorio = asi.id_consultorio
        WHERE u.id_usuario = ?";
        $params_doc = [$id_usuario];
        $stmt = sqlsrv_query($conn, $sql, $params_doc);

        if ($stmt === false) {
            throw new Exception("Error al obtener los datos del doctor.");
        }

        $doctor_data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$doctor_data) {
            throw new Exception("No se encontraron datos del doctor.");
        }
        // Asignar a variables individuales:
$nombre_especialidad = $doctor_data['nombre_especialidad'];
$numero_consultorio = $doctor_data['numero_consultorio'];
$cedula             = $doctor_data['cedula'];
$nombre_doctor      = $doctor_data['nombre_doctor'];

        //obtenemos los datos que nos faltan del paciente
        $sql = "SELECT
CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) AS nombre_paciente
FROM usuario u 
INNER JOIN paciente p ON p.id_usuario = u.id_usuario
WHERE p.id_paciente = ?";

        $params_paciente = [$id_paciente];

        $stmt = sqlsrv_query($conn, $sql, $params_paciente);

        if ($stmt === false) {
            throw new Exception("Error al obtener los datos del paciente.");
        }

        $paciente_data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$paciente_data) {
            throw new Exception("No se encontraron datos del paciente.");
        }

        // Asignar los datos del paciente a variables
        $nombre_paciente = $paciente_data['nombre_paciente'];



        //obtenemos el id maximo de receta
        

        // Insertamos la nueva receta
        $insert_receta = "EXEC sp_generar_receta ?, ?, ?, ?, ?";
        $params_receta = [$id_paciente, 
                   $diagnostico, 
                   $nombre_medicamento, 
                   $dosis, 
                   $observaciones];

        $stmt = sqlsrv_query($conn, $insert_receta, $params_receta);
       if ($stmt === false) {
    $errors = sqlsrv_errors();
    throw new Exception("Error al insertar la receta: " . print_r($errors, true));
}




        //obtenemos el id maximo de la bitacora
        $max_id_bitacora = "SELECT MAX(id_bitacora) AS max_id FROM bitacora";
        $stmt = sqlsrv_query($conn, $max_id_bitacora);
        if ($stmt === false) {
            throw new Exception("Error al obtener el ID máximo de la bitácora.");
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $nuevo_id_bitacora = $row['max_id'] + 1;

        // Insertamos la nueva bitácora
        $insert_bitacora = "INSERT INTO bitacora (id_bitacora, fecha_movimiento, especialidad, nombre_paciente, consultorio, cedula, nombre_doctor, id_cita, id_receta, id_paciente) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params_bitacora= [$nuevo_id_bitacora, 
                    $fechaHora, 
                    $nombre_especialidad, 
                    $nombre_paciente, 
                    $numero_consultorio, 
                    $cedula, 
                    $nombre_doctor, 
                    $id_cita, 
                    $nuevo_id_receta, 
                    $id_paciente];

        $stmt = sqlsrv_query($conn, $insert_bitacora, $params_bitacora);
        if ($stmt === false) {
            throw new Exception("Error al insertar la bitácora: " . print_r(sqlsrv_errors(), true));
        }
        header('Content-Type: application/json');
       echo json_encode(["success" => true, "message" => "Cita finalizada exitosamente"]);
exit;


    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }
}

?>
