<?php
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

$servidor_db = "localhost";
$usuario_bd = "root";
$contrasena_bd = "";
$nombre_bd = "bootcamp2";

$conexion = new mysqli($servidor_db, $usuario_bd, $contrasena_bd, $nombre_bd);

if ($conexion->connect_error) {
    die ("error de conexion a mysql:" . $conexion->connect_error);

}
if (!$conexion->set_charset("utf8mb4")) {
    die ("error al configurar UTF-8:" . $conexion->connect_error);
}
