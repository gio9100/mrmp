<?php
// --- DEFINICIÓN DE CREDENCIALES ---
// $host: Dirección del servidor de base de datos. 'localhost' significa que está en la misma máquina.
$host = "localhost";      

// $user: Nombre de usuario para autenticarse en MySQL. 'root' es el administrador por defecto en XAMPP.
$user = "root";           

// $pass: Contraseña del usuario. En XAMPP, por defecto viene vacía ("").
$pass = "";               

// $db: Nombre de la base de datos específica a la que queremos conectarnos.
$db   = "bootcamp2";      

// --- CREACIÓN DE LA CONEXIÓN ---
// new mysqli(): Crea una nueva INSTANCIA (objeto) de la clase mysqli.
// Esta línea intenta abrir una conexión física con el servidor MySQL usando los datos provistos.
$conexion = new mysqli($host, $user, $pass, $db);

// --- VERIFICACIÓN DE ERRORES ---
// -> (Operador flecha): Se usa para acceder a propiedades o métodos del objeto '$conexion'.
// connect_errno: Propiedad que contiene el código de error de la última llamada (0 si no hubo error).
if ($conexion->connect_errno) {
    // die(): Detiene la ejecución del script y muestra el mensaje entre paréntesis.
    // . (punto): Es el operador de concatenación en PHP (une cadenas de texto).
    die("Error al conectar a la base de datos: (" . $conexion->connect_errno . ") " . $conexion->connect_error);
}

// --- CONFIGURACIÓN DE CARACTERES ---
// set_charset("utf8mb4"): Configura la codificación de caracteres de la conexión a UTF-8 (multibyte).
// Esto es VITAL para soportar acentos, eñes y emojis correctamente en la base de datos.
// 'utf8mb4' es superior a 'utf8' porque soporta el set completo de Unicode (incluyendo emojis).
$conexion->set_charset("utf8mb4");
?>
