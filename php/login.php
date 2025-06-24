<?php
require_once __DIR__ . "/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/login.html");
    exit();
}

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($username) || empty($password)) {
    header("Location: ../html/login.html?error=2");
    exit();
}

// 1. Verificar usuario por correo y contraseña
$sql = "SELECT id_tipo_usuario, contrasena  FROM usuario WHERE correo = ?";
$params = [ $username ];

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("Error en la consulta de usuario: " . print_r(sqlsrv_errors(), true));
}

if ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($password === $row['contrasena']) {
        session_start();
        $_SESSION['id_usuario'] = $row['id_usuario'];
        $_SESSION['id_tipo_usuario'] = $row['id_tipo_usuario'];

        // 2. Redirigir según tipo de usuario
        switch ($row['id_tipo_usuario']) {
            case 1:
                header("Location: ../html/mainPaciente.html");
                break;
            case 2:
                header("Location: ../html/mainDoctor.html");
                break;
            case 3:
                header("Location: ../html/mainRecepcionista.html");
                break;
            default:
                header("Location: ../html/login.html?error=4"); // tipo inválido
                break;
        }
        exit();
    } else {
        header("Location: ../html/login.html?error=1"); // contraseña incorrecta
        exit();
    }
} else {
    header("Location: ../html/login.html?error=1"); // usuario no encontrado
    exit();
}
