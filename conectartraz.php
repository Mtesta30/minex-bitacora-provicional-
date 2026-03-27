<?php
$Servidor = "10.0.0.4";
$connectionInfo = [
    "Database" => "Traz",
    "UID" => "Soporte_2026",
    "PWD" => "Soporte.2026*",
    "TrustServerCertificate" => true,
    "Encrypt" => false
];

$conn = sqlsrv_connect($Servidor, $connectionInfo);

if ($conn === false) {
    $errors = print_r(sqlsrv_errors(), true);
    die("No se pudo conectar a la base de datos: $errors");
}
