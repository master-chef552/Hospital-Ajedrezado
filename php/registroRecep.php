<?php
// registroRecep.php
session_start();
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../html/mainRecepcionista.html');
    exit;
}

// 1) Recoger y sanitizar
$tipoUsuario    = trim($_POST['tipoUsuario'] ?? '');
$nombre         = ucwords(strtolower(trim($_POST['nombre'] ?? '')));
$apPaterno      = ucwords(strtolower(trim($_POST['apellidoPaterno'] ?? '')));
$apMaterno      = ucwords(strtolower(trim($_POST['apellidoMaterno'] ?? '')));
$fechaNac       = $_POST['fechaDeNacimiento'] ?? '';
$telefono       = trim($_POST['telefono'] ?? '');
$nombreUsuario  = trim($_POST['nombreDeUsuario'] ?? '');
$correo         = strtolower(trim($_POST['correo'] ?? ''));
$pass           = $_POST['contrasena'] ?? '';
$pass2          = $_POST['contrasena2'] ?? '';
$tipoEmpleado   = trim($_POST['tipoEmpleado'] ?? '');
$especialidad   = trim($_POST['especialidad'] ?? '');

// 2) Validaciones básicas
if (
    !$tipoUsuario || !$nombre || !$apPaterno || !$apMaterno ||
    !$fechaNac || !$telefono || !$nombreUsuario ||
    !$correo || !$pass || !$pass2
) {
    die('Todos los campos son obligatorios.');
}
if ($pass !== $pass2) {
    die('Las contraseñas no coinciden.');
}

// 3) Determinar id_tipo_usuario
if ($tipoUsuario === 'paciente') {
    $idTipoUsr = 1;
} elseif ($tipoUsuario === 'empleado') {
    if ($tipoEmpleado === 'Doctor') {
        $idTipoUsr = 2;
        if (empty($especialidad)) {
            die('Debes seleccionar una especialidad para el doctor.');
        }
    } elseif ($tipoEmpleado === 'Recepcionista') {
        $idTipoUsr = 3;
    } else {
        die('Debes seleccionar tipo de empleado.');
    }
} else {
    die('Tipo de usuario no válido.');
}

// 4) Iniciar transacción
if (!sqlsrv_begin_transaction($conn)) {
    die('Error al iniciar transacción: ' . print_r(sqlsrv_errors(), true));
}

try {
    // ——— Insertar en usuario ———
    $res = sqlsrv_query($conn, "SELECT ISNULL(MAX(id_usuario),0)+1 AS next_id FROM usuario");
    $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
    $nextUsr = (int)$row['next_id'];
    sqlsrv_free_stmt($res);

    $sql = "
      INSERT INTO usuario
        (id_usuario, nombre, ap_paterno, ap_materno,
         fecha_nacimiento, telefono, contrasena,
         nombre_usuario, correo, id_tipo_usuario)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $params = [
        $nextUsr, $nombre, $apPaterno, $apMaterno,
        $fechaNac, $telefono, $pass,
        $nombreUsuario, $correo, $idTipoUsr
    ];
    if (!sqlsrv_query($conn, $sql, $params)) {
        throw new Exception('Error al insertar usuario: ' . print_r(sqlsrv_errors(), true));
    }

    // ——— Si es empleado, insertamos en empleado ———
    if ($tipoUsuario === 'empleado') {
        $res = sqlsrv_query($conn, "SELECT ISNULL(MAX(id_empleado),0)+1 AS next_id FROM empleado");
        $row = sqlsrv_fetch_array($res, SQLSRV_FETCH_ASSOC);
        $nextEmp = (int)$row['next_id'];
        sqlsrv_free_stmt($res);

        // id_tipo_empleado: 1=Recepcionista, 2=Doctor según tu tabla tipo_empleado
        $idTipoEmp = ($tipoEmpleado === 'Doctor') ? 2 : 1;

        $sql = "
          INSERT INTO empleado
            (id_empleado, id_usuario, salario, estatus, rfc, id_tipo_empleado)
          VALUES (?, ?, NULL, 'Activo', NULL, ?)
        ";
        if (!sqlsrv_query($conn, $sql, [ $nextEmp, $nextUsr, $idTipoEmp ])) {
            throw new Exception('Error al insertar empleado: ' . print_r(sqlsrv_errors(), true));
        }

        // ——— Si es Doctor, guardamos en doctor y doctor_especialidad ———
        if ($tipoEmpleado === 'Doctor') {
            // 1) doctor
            $cedula = (string)$nextEmp;  // usamos el mismo id_empleado como cédula
            if (!sqlsrv_query($conn, "INSERT INTO doctor (cedula, id_empleado) VALUES (?, ?)", [ $cedula, $nextEmp ])) {
                throw new Exception('Error al insertar doctor: ' . print_r(sqlsrv_errors(), true));
            }

            // 2) buscamos id_especialidad
            $stmt = sqlsrv_query(
                $conn,
                "SELECT id_especialidad FROM especialidad WHERE nombre_especialidad = ?",
                [ $especialidad ]
            );
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (!$row) {
                throw new Exception("Especialidad no encontrada: $especialidad");
            }
            $idEsp = $row['id_especialidad'];

            // 3) doctor_especialidad
            $res2 = sqlsrv_query($conn, "SELECT ISNULL(MAX(id_doc_esp),0)+1 AS next_id FROM doctor_especialidad");
            $row2 = sqlsrv_fetch_array($res2, SQLSRV_FETCH_ASSOC);
            $nextDocEsp = (int)$row2['next_id'];
            sqlsrv_free_stmt($res2);

            $sql = "
              INSERT INTO doctor_especialidad
                (id_doc_esp, cedula, id_especialidad, estatus)
              VALUES (?, ?, ?, 'Activo')
            ";
            if (!sqlsrv_query($conn, $sql, [ $nextDocEsp, $cedula, $idEsp ])) {
                throw new Exception('Error al insertar doctor_especialidad: ' . print_r(sqlsrv_errors(), true));
            }
        }
    }

    // ——— Commit y redirección ———
    if (!sqlsrv_commit($conn)) {
        throw new Exception('Error al confirmar transacción: ' . print_r(sqlsrv_errors(), true));
    }

    header('Location: ../html/mainRecepcionista.html?user_created=1');
    exit;
}
catch (Exception $e) {
    sqlsrv_rollback($conn);
    die('Transacción abortada: ' . $e->getMessage());
}
?>
