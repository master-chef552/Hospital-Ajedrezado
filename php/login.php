<?php
// inicio de php login
// login.php

// 1) Incluir el archivo de conexión, de modo que $conn sea un recurso SQLSRV.
require_once __DIR__ . "/conexion.php"; 
// Ajusta la ruta si tu estructura de carpetas difiere.

// 2) Verificar que la petición venga por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Si intentan acceder directamente vía GET, redirigimos al formulario de login.
    header("Location: ../html/login.html");
    exit();
}

// 3) Recoger y sanitizar los campos enviados
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    // Error: faltan datos
    header("Location: ../html/login.html?error=2");
    exit();
}

// 4) Consultar en la tabla usuario si existe ese username
$sql = "SELECT id_usuario, id_paciente, username, password 
        FROM usuario 
        WHERE username = ?";
$params = [ $username ];

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    // Error en la consulta
    die("Error al consultar usuario: " . print_r(sqlsrv_errors(), true));
}

// 5) Verificar si se obtuvo alguna fila
if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // 6) Comparar el hash de la BD con la contraseña ingresada
    $hashedPassword = $row['password']; // string con el hash generado por password_hash()
    if (password_verify($password, $hashedPassword)) {
        // 7) La contraseña es correcta → inicio de sesión y redirección

        // (Opcional) Iniciar session para almacenar datos del usuario
        session_start();
        // Por ejemplo, podemos guardar el id_usuario e id_paciente:
        $_SESSION['id_usuario']  = $row['id_usuario'];
        $_SESSION['id_paciente'] = $row['id_paciente'];
        $_SESSION['username']    = $row['username'];

        // 8) Redirigir a la página de paciente (puede ser mainPaciente.html o un PHP que cargue datos del paciente)
        header("Location: ../html/mainPaciente.html");
        exit();
    } else {
        // Contraseña incorrecta
        sqlsrv_free_stmt($stmt);
        header("Location: ../html/login.html?error=1");
        exit();
    }
} else {
    // No existe ningún registro con ese username
    sqlsrv_free_stmt($stmt);
    header("Location: ../html/login.html?error=1");
    exit();
}


//fin de php login
?>