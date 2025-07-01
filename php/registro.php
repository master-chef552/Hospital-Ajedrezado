<?php
// registro.php
session_start();
require_once __DIR__ . "/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../html/createAccount.html");
    exit;
}

// 1) Recoger y sanitizar
$nombre        = ucwords(strtolower(trim($_POST['nombre'] ?? "")));
$ap_paterno    = ucwords(strtolower(trim($_POST['ap_paterno'] ?? "")));
$ap_materno    = ucwords(strtolower(trim($_POST['ap_materno'] ?? "")));
$fecha_nac     = $_POST['fecha_nacimiento'] ?? "";
$CURP         = strtoupper(trim($_POST['curp'] ?? ""));
$telefono      = trim($_POST['telefono'] ?? "");
$correo        = strtolower(trim($_POST['correo'] ?? ""));
$nombre_usuario= strtolower(trim($_POST['nombre_usuario'] ?? ""));
$password      = $_POST['contrasena'] ?? "";
$confirmPass   = $_POST['confirmarContrasena'] ?? "";
$idTipoUsr     = intval($_POST['id_tipo_usuario'] ?? 1);

// 2) Validaciones básicas
if (
    empty($nombre) || empty($ap_paterno) || empty($ap_materno) ||
    empty($fecha_nac) || empty($CURP) || empty($telefono) ||
    empty($correo) || empty($password) || empty($confirmPass)
) {
    die("Todos los campos son obligatorios.");
}
if ($password !== $confirmPass) {
    die("Las contraseñas no coinciden.");
}

// 3) Iniciar transacción
if (!sqlsrv_begin_transaction($conn)) {
    die("No se pudo iniciar transacción: " . print_r(sqlsrv_errors(), true));
}

try {
    // — Insertar en usuario —
    $sqlMaxUsr = "SELECT ISNULL(MAX(id_usuario),0)+1 AS next_id FROM usuario";
    $resMaxUsr = sqlsrv_query($conn, $sqlMaxUsr);
    $rowUsr    = sqlsrv_fetch_array($resMaxUsr, SQLSRV_FETCH_ASSOC);
    $nextUsr   = (int)$rowUsr['next_id'];
    sqlsrv_free_stmt($resMaxUsr);

    $sqlUsr = "
      INSERT INTO usuario
        (id_usuario, nombre, ap_paterno, ap_materno,
         fecha_nacimiento, telefono, contrasena,
         nombre_usuario, correo, id_tipo_usuario)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $paramsUsr = [
      $nextUsr, $nombre, $ap_paterno, $ap_materno,
      $fecha_nac, $telefono, $password,
      $nombre_usuario, $correo, $idTipoUsr
    ];
    $stmtUsr = sqlsrv_query($conn, $sqlUsr, $paramsUsr);
    if ($stmtUsr === false) {
        throw new Exception("Error insert usuario: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmtUsr);

    // — Insertar en paciente (solo la referencia) —
    $sqlMaxPac = "SELECT ISNULL(MAX(id_paciente),0)+1 AS next_id FROM paciente";
    $resMaxPac = sqlsrv_query($conn, $sqlMaxPac);
    $rowPac    = sqlsrv_fetch_array($resMaxPac, SQLSRV_FETCH_ASSOC);
    $nextPac   = (int)$rowPac['next_id'];
    sqlsrv_free_stmt($resMaxPac);

    $sqlPac = "
      INSERT INTO paciente
        (id_paciente, id_usuario, curp)
      VALUES (?, ?, ?)
    ";
    $paramsPac = [ $nextPac, $nextUsr, $CURP ];
    $stmtPac   = sqlsrv_query($conn, $sqlPac, $paramsPac);
    if ($stmtPac === false) {
        throw new Exception("Error insert paciente: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmtPac);

    // — Commit y redirección —
    if (!sqlsrv_commit($conn)) {
        throw new Exception("Error al confirmar: " . print_r(sqlsrv_errors(), true));
    }
    header("Location: ../html/login.html?registered=1");
    exit;
}
catch (Exception $e) {
    sqlsrv_rollback($conn);
    die("Transacción abortada: " . $e->getMessage());
}
?>
