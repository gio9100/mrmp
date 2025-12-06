<?php
// Define las credenciales de la base de datos
// $host: Dirección del servidor (localhost para XAMPP)
$host = "localhost";      
// $user: Usuario administrador de MySQL (root por defecto)
$user = "root";           
// $pass: Contraseña del usuario (vacía por defecto en XAMPP)
$pass = "";               
// $db: Nombre de la base de datos a conectar
$db   = "bootcamp2";      

// Crea una nueva instancia de la clase mysqli para conectar
// El operador 'new' instancia el objeto de conexión
$conexion = new mysqli($host, $user, $pass, $db);

// Verifica si hubo un error en la conexión
// connect_errno: Propiedad que contiene el código de error (0 si no hay error)
if ($conexion->connect_errno) {
    // die: Detiene la ejecución del script y muestra el mensaje
    die("Error al conectar a la base de datos: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

// Establece el conjunto de caracteres a UTF-8 (mb4)
// Esto asegura que tildes, ñ y emojis se guarden y muestren correctamente
$conexion->set_charset("utf8mb4");
?>
