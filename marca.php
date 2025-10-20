<?php
session_start();
require_once "conexion.php";

// Solo usuarios logueados
if(!isset($_SESSION['usuario_id'])){
    header("Location: inicio_secion.php");
    exit;
}

// Mensaje
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// Obtener ID de la marca
$marca_id = intval($_GET['id'] ?? 0);

if($marca_id <= 0){
    header("Location: dashboard.php");
    exit;
}

// Obtener info de la marca
$stmt = $conexion->prepare("SELECT * FROM marcas WHERE id=?");
$stmt->bind_param("i", $marca_id);
$stmt->execute();
$marca_res = $stmt->get_result();
$marca = $marca_res->fetch_assoc();
$stmt->close();

if(!$marca){
    header("Location: dashboard.php");
    exit;
}

// Obtener piezas de esa marca
$stmt = $conexion->prepare("SELECT * FROM piezas WHERE marca_id=? ORDER BY id DESC");
$stmt->bind_param("i", $marca_id);
$stmt->execute();
$piezas_res = $stmt->get_result();
$piezas = $piezas_res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($marca['nombre']) ?> - MRMC</title>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<header>
    <div class="logo">MRMC Logo</div>
    <h1><?= htmlspecialchars($marca['nombre']) ?> - MRMC</h1>
    <div>
        <a href="dashboard.php" style="color:#ffd700;text-decoration:none;">Inicio</a>
        <a href="logout.php" style="color:#ffd700;text-decoration:none;margin-left:15px;">Cerrar sesión</a>
    </div>
</header>

<main>

<?php if($mensaje): ?>
<p class="mensaje success"><?= htmlspecialchars($mensaje) ?></p>
<?php endif; ?>

<div style="margin-bottom:20px; text-align:center;">
    <h2>Piezas de <?= htmlspecialchars($marca['nombre']) ?></h2>
</div>

<!-- Piezas -->
<div class="piezas">
    <?php if(count($piezas) == 0): ?>
        <p style="color:white;">No se encontraron piezas para esta marca.</p>
    <?php endif; ?>

    <?php foreach($piezas as $p): ?>
    <div class="pieza">
        <?php if($p['imagen'] && file_exists("uploads/".$p['imagen'])): ?>
            <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
        <?php else: ?>
            <img src="uploads/default.png" alt="Sin imagen">
        <?php endif; ?>
        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
        <p><?= htmlspecialchars($p['descripcion']) ?></p>
        <p class="precio">$<?= number_format($p['precio'],2) ?></p>
        <p>Stock: <?= intval($p['cantidad']) ?></p>
        <!-- Agregar al carrito -->
        <form method="post" action="carrito.php">
            <input type="hidden" name="id_pieza" value="<?= $p['id'] ?>">
            <input type="hidden" name="volver_a" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <button type="submit">Agregar al carrito</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

</main>

<footer>
&copy; <?= date('Y') ?> MRMC - Mexican Racing Motor Car
</footer>

</body>
</html>
