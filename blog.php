<?php
session_start();
require_once "conexion.php";
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mexican Racing Motor Parts</title>
    <link rel="stylesheet" href="blog.css">
</head>
<header>  
    <div class="logo">
        <img src="img/mrmp logo.png" alt="MRMP logo">
        <p>Mexican Racing Motor Parts</p>
    </div>
    <div class="usuario">
        <?php if(isset($_SESSION['usuario_id'])): ?>
      <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'])?></span>
      <a href="perfil.php">Perfil</a>
      <a href="carrito.php">Carrito</a> (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a>
      <a href="dashboard-piezas.php?logout=1">Cerrar Sesion</a>
      <a href="dashboard-piezas.php">dashboard</a>
       <?php else: ?>
        <a href="inicio_secion.php">Iniciar Sesion</a>
        <a href="register.php">Crear Cuenta</a>
        <a href="dashboard-piezas.php">dashboard</a>
        <?php endif; ?>
    </div>
    </header>
<body>
    <p>Somos un Taller/tienda de autopartes, manejamos casi todas las marcas de carros (Toyota, Subaru, Dodge, Nissan, Ford,Chevrolet,Honda, Mitsubihi,bmw,mercedes benz,audi,etc)</p>
</body>
</html>