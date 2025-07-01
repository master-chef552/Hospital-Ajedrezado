<?php
// bajaDoctor.php
session_start();
require_once __DIR__ . '/conexion.php';

// Verificar sesión
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Solo admitimos método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$action = $_GET['action'] ?? '';
header('Content-Type: application/json; charset=UTF-8');

if ($action === 'buscar') {
    // 1) Recuperar cédula
    $cedula = trim($_POST['cedula'] ?? '');
    if ($cedula === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta la cédula']);
        exit;
    }

    // 2) Consulta de datos del doctor
    $sql = "SELECT 
        d.cedula,
        CONCAT(u.nombre, ' ', u.ap_paterno, ' ', u.ap_materno) AS nombre,
        u.telefono,
        u.fecha_nacimiento
      FROM usuario u
      INNER JOIN empleado e ON e.id_usuario = u.id_usuario
      INNER JOIN doctor d ON d.id_empleado = e.id_empleado
      WHERE d.cedula = ?
    ";
    $stmt = sqlsrv_query($conn, $sql, [ $cedula ]);
    if ($stmt === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la consulta de doctor', 'details' => sqlsrv_errors()]);
        exit;
    }

    $doctor = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if (!$doctor) {
        http_response_code(404);
        echo json_encode(['error' => 'Doctor no encontrado']);
        exit;
    }

    // 3) Consulta de citas pendientes para ese doctor
$sql2 = "SELECT COUNT(*) AS cnt 
  FROM cita 
  WHERE cedula = ? 
    AND id_estado_cita = 2
";
$stmt2 = sqlsrv_query($conn, $sql2, [ $cedula ]);
if ($stmt2 === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta de citas', 'details' => sqlsrv_errors()]);
    exit;
}
$row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
$hayPendientes = (intval($row2['cnt'] ?? 0) > 0) ? 'si' : 'no';

// 4) Devolver JSON combinado
echo json_encode([
  'cedula'     => $doctor['cedula'],
  'nombre'     => $doctor['nombre'],
  'telefono'   => $doctor['telefono'],
  'fecha_nac'  => $doctor['fecha_nacimiento']->format('Y-m-d'),
  'pendientes' => $hayPendientes
]);
exit;

}


if ($action === 'darBaja') {
    // 4) Recuperar cédula de POST
    $cedula = trim($_POST['cedula'] ?? '');
    if ($cedula === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta la cédula']);
        exit;
    }

    $sql2 = 'SELECT id_empleado FROM doctor WHERE cedula = ?';
    $stmt2 = sqlsrv_query($conn, $sql2, [ $cedula ]);
    if ($stmt2 === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al consultar el doctor', 'details' => sqlsrv_errors()]);
        exit;
    }
    $row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
    $id_empleado = $row2['id_empleado'] ?? null;


    $sql = 'UPDATE empleado SET estatus = ? WHERE id_empleado = ?';
    $params = [ 'Inactivo', $id_empleado ];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        http_response_code(500);
        echo json_encode([
            'error'   => 'Error al dar de baja al doctor',
            'details' => sqlsrv_errors()
        ]);
        exit;
    }

    echo json_encode([
        'message' => 'Doctor con cédula ' . $cedula . ' dado de baja exitosamente.'
    ]);
    exit;
}

// Si no coincide ninguna acción
http_response_code(400);
echo json_encode(['error'=>'Acción inválida']);
exit;

