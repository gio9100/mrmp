<?php


$host = "localhost";      // Servidor MySQL, usualmente localhost
$user = "root";           // Usuario MySQL
$pass = "";               // Contraseña MySQL
$db   = "bootcamp2";      // Nombre de tu base de datos

// Crear la conexión
$conexion = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conexion->connect_errno) {
    // En caso de error, se detiene el script y muestra el error
    die("Error al conectar a la base de datos: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

// Opcional: establecer codificación de caracteres
$conexion->set_charset("utf8mb4");
?>
