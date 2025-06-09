<?php
//inicio de php conexion.php
$serverName = "SRUFACE_DE_NICO\SQLEXPRESS"; // o "localhost", según tu configuración
$connectionOptions = [
    "Database" => "IPN_MEDICAL_CENTER",
    "Uid" => "nicolas",
    "PWD" => "durango45!",
    "CharacterSet" => "UTF-8"
];

// Conectar
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Verificar conexión
if ($conn) {
    echo "✅ Conexión exitosa a SQL Server.";
} else {
    echo "❌ Error en la conexión:<br>";
    die(print_r(sqlsrv_errors(), true));
}
//fin de php conexion.php
?>
