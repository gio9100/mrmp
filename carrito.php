<?php
session_start();
require_once "conexion.php";

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}

// Inicializar carrito
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Eliminar pieza del carrito
if(isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    if(isset($_SESSION['carrito'][$id])){
        unset($_SESSION['carrito'][$id]);
    }
    header("Location: carrito.php");
    exit;
}

// Actualizar cantidades
if(isset($_POST['cantidad'])){
    foreach($_POST['cantidad'] as $id => $cant){
        $id = intval($id);
        $cant = intval($cant);
        if($cant <= 0){
            unset($_SESSION['carrito'][$id]);
        } else {
            // Validar stock
            $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
            $stmt->bind_param("i",$id);
            $stmt->execute();
            $res = $stmt->get_result();
            if($pieza = $res->fetch_assoc()){
                $_SESSION['carrito'][$id] = min($cant, $pieza['cantidad']);
            }
            $stmt->close();
        }
    }
    header("Location: carrito.php");
    exit;
}

// Procesar pedido
if(isset($_POST['procesar_pedido']) && !empty($_SESSION['carrito'])){
    $usuario_id = $_SESSION['usuario_id'];
    $detalles = json_encode($_SESSION['carrito']);
    $fecha = date('Y-m-d H:i:s');
    $stmt = $conexion->prepare("INSERT INTO pedidos (usuario_id, detalles_pedido, fecha, estado) VALUES (?, ?, ?, 'pendiente')");
    $stmt->bind_param("iss", $usuario_id, $detalles, $fecha);
    $stmt->execute();
    $stmt->close();
    $_SESSION['carrito'] = [];
    $_SESSION['mensaje'] = "✅ Pedido realizado correctamente.";
    header("Location: carrito.php");
    exit;
}

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito MRMP</title>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="img/mrmp logo.png" alt="MRMP logo">
    <p>Mexican Racing Motor Parts</p>
  </div>
  <div class="usuario">
    <?php if(isset($_SESSION['usuario_id'])): ?>
      <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
      <a href="dashboard-piezas.php">Dashboard</a>
      <a href="logout.php">Cerrar sesión</a>
    <?php else: ?>
      <a href="inicio_secion.php">Iniciar sesión</a>
      <a href="register.php">Crear cuenta</a>
    <?php endif; ?>
  </div>
</header>

<main>
<h2>🛒 Tu Carrito de Cotización</h2>

<?php if($mensaje): ?>
<div class="modal-mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if(empty($_SESSION['carrito'])): ?>
<div class="carrito-vacio" style="max-width:400px; margin:20px auto; text-align:center; padding:25px; border:1px solid #c62828; border-radius:8px;">
    <p>El carrito está vacío.</p>
    <a href="dashboard-piezas.php" class="btn" style="background:#c62828;color:#fff;border-radius:6px;padding:8px 16px;text-decoration:none;">← Volver al Dashboard</a>
</div>
<?php else: ?>
<form method="post">
<table style="border-collapse:collapse;width:90%;max-width:800px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
    <tr style="background:#c62828;color:#fff;">
        <th>Pieza</th>
        <th>Cantidad</th>
        <th>Stock</th>
        <th>Acciones</th>
    </tr>
    <?php foreach($_SESSION['carrito'] as $id => $cant):
        $stmt = $conexion->prepare("SELECT nombre, cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pieza = $res->fetch_assoc();
        $stmt->close();
    ?>
    <tr>
        <td><?= htmlspecialchars($pieza['nombre']) ?></td>
        <td><input type="number" name="cantidad[<?= $id ?>]" value="<?= $cant ?>" min="1" max="<?= $pieza['cantidad'] ?>" class="cantidad-input"></td>
        <td><?= $pieza['cantidad'] ?></td>
        <td><a href="carrito.php?eliminar=<?= $id ?>" class="btn" style="background:#ef4444;color:#fff;border-radius:6px;padding:6px 12px;">Eliminar</a></td>
    </tr>
    <?php endforeach; ?>
</table>
<div style="text-align:center;margin:15px 0;">
    <button type="submit" class="btn" style="background:#c62828;color:#fff;padding:8px 16px;border-radius:6px;">Actualizar Cantidades</button>
    <button type="submit" name="procesar_pedido" class="btn" style="background:#10b981;color:#fff;padding:8px 16px;border-radius:6px;">Procesar Pedido</button>
</div>
</form>
<?php endif; ?>
</main>

<footer style="padding:8px 0;font-size:0.85rem;text-align:center;border-top:2px solid #c62828;">
    © <?= date('Y') ?> <span>MRMP</span>
</footer>

</body>
</html>
