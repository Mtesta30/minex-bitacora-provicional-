<?php
$Servidor = "127.0.0.1,1433";
$connectionInfo = [
    "Database" => "Traz",
    "UID" => "Sa",
    "PWD" => "123456",
    "TrustServerCertificate" => true,
    "Encrypt" => false
];

$conn = sqlsrv_connect($Servidor, $connectionInfo);

if ($conn === false) {
    $errors = print_r(sqlsrv_errors(), true);
    die("No se pudo conectar a la base de datos: $errors");
}