<?php
// inicio de registro.php

// 1) Incluir el archivo de conexión: deja disponible $conn (recurso SQLSRV)
require_once __DIR__ . "/conexion.ph
p";

// 2) Asegurarnos de que llegó por POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 3) Recoger y sanitizar datos del formulario
    $dni             = strtoupper(trim($_POST['dni'] ?? ""));
    $nombres         = ucwords(strtolower(trim($_POST['nombres'] ?? "")));
    $apellidos       = ucwords(strtolower(trim($_POST['apellidos'] ?? "")));
    $curp            = strtoupper(trim($_POST['curp'] ?? ""));
    $fechaNac        = $_POST['fechaNacimiento'] ?? ""; // formato YYYY-MM-DD
    $domicilio       = trim($_POST['domicilio'] ?? "");
    $telefono        = trim($_POST['telefono'] ?? "");
    $email           = strtolower(trim($_POST['email'] ?? ""));
    $password        = $_POST['contrasena'] ?? "";
    $confirmPassword = $_POST['confirmarContrasena'] ?? "";


    // 4) Validaciones básicas
    if (
        empty($dni) || empty($nombres) || empty($apellidos) || empty($curp) ||
        empty($fechaNac) || empty($domicilio) || empty($telefono) ||
        empty($email) || empty($password) || empty($confirmPassword)
    ) {
        die("Todos los campos son obligatorios.");
    }
    if ($password !== $confirmPassword) {
        die("Las contraseñas no coinciden.");
    }

    // 5) Validar unicidad de CURP y DNI en la tabla paciente
    $sqlCheck = "
        SELECT COUNT(*) AS cnt
        FROM paciente 
        WHERE CURP = ? OR dni = ?
    ";
    $paramsCheck = [ $curp, $dni ];
    
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);
    if ($stmtCheck === false) {
        die("Error al verificar CURP/DNI: " . print_r(sqlsrv_errors(), true));
    }
    $rowCheck = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
    if ($rowCheck['cnt'] > 0) {
        die("Ya existe un paciente con esa CURP o DNI.");
    }
    sqlsrv_free_stmt($stmtCheck);

    // 6) Iniciar transacción
    if (!sqlsrv_begin_transaction($conn)) {
        die("No se pudo iniciar la transacción: " . print_r(sqlsrv_errors(), true));
    }

    try {
        // 7) Obtener próximo id_paciente
        $sqlMaxPac = "SELECT ISNULL(MAX(id_paciente), 0) + 1 AS next_id FROM paciente";
        $stmtMaxPac = sqlsrv_query($conn, $sqlMaxPac);
        if ($stmtMaxPac === false) {
            throw new Exception("Error al obtener siguiente id_paciente: " . print_r(sqlsrv_errors(), true));
        }
        $rowMaxPac = sqlsrv_fetch_array($stmtMaxPac, SQLSRV_FETCH_ASSOC);
        $nextIdPac = (int) $rowMaxPac['next_id'];
        sqlsrv_free_stmt($stmtMaxPac);

        // 8) Insertar en paciente
        $sqlInsertPac = "
            INSERT INTO paciente (
                id_paciente, dni, CURP, nombres, apellidos,
                fechanac, domicilio, telefono, email, observacion
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, NULL
            )
        ";
        $paramsPac = [
            $nextIdPac,
            $dni,
            $curp,
            $nombres,
            $apellidos,
            $fechaNac,   // SQL Server acepta 'YYYY-MM-DD' para date
            $domicilio,
            $telefono,
            $email
        ];
        $stmtInsPac = sqlsrv_query($conn, $sqlInsertPac, $paramsPac);
        if ($stmtInsPac === false) {
            throw new Exception("Error al insertar paciente: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtInsPac);

        // 9) Obtener próximo id_usuario
        $sqlMaxUser = "SELECT ISNULL(MAX(id_usuario), 0) + 1 AS next_id FROM usuario";
        $stmtMaxUser = sqlsrv_query($conn, $sqlMaxUser);
        if ($stmtMaxUser === false) {
            throw new Exception("Error al obtener siguiente id_usuario: " . print_r(sqlsrv_errors(), true));
        }
        $rowMaxUser = sqlsrv_fetch_array($stmtMaxUser, SQLSRV_FETCH_ASSOC);
        $nextIdUser = (int) $rowMaxUser['next_id'];
        sqlsrv_free_stmt($stmtMaxUser);

        // 10) Hashear la contraseña (usamos password_hash de PHP)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // 11) Insertar en usuario
        $sqlInsertUser = "
            INSERT INTO usuario (
                id_usuario, id_paciente, username, password
            ) VALUES (
                ?, ?, ?, ?
            )
        ";
        $paramsUser = [
            $nextIdUser,
            $nextIdPac,
            $email,          // username en la tabla usuario
            $hashedPassword
        ];
        $stmtInsUser = sqlsrv_query($conn, $sqlInsertUser, $paramsUser);
        if ($stmtInsUser === false) {
            throw new Exception("Error al insertar usuario: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_free_stmt($stmtInsUser);

        // 12) Commit de la transacción
        if (!sqlsrv_commit($conn)) {
            throw new Exception("Error al confirmar la transacción: " . print_r(sqlsrv_errors(), true));
        }

        // 13) Redirigir al login
        header("Location: ../html/login.html");
        exit();
    }
    catch (Exception $e) {
        // Si algo falla, hacemos rollback
        sqlsrv_rollback($conn);
        die("⚠️ Transacción cancelada: " . $e->getMessage());
    }


} else {
    // Si no es POST, redirigimos al formulario
    header("Location: ../html/createAccount.html");
    exit();
}


// fin de php registro
?>
