<?php
// Opcional: ocultar warnings/notices
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Datos de conexión
$serverName = "SRUFACE_DE_NICO\\SQLEXPRESS";
$connectionOptions = [
    "Database"     => "ipn_prueba",
    "Uid"          => "nicolas",
    "PWD"          => "durango45!",
    "CharacterSet" => "UTF-8"
];

// Conectar
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'error'   => 'Error de conexión a la base de datos',
        'details' => sqlsrv_errors()
    ]);
    exit;
}

// No imprimir nada en caso de éxito
