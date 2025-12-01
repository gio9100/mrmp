<?php
session_start();
require_once "conexion.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_panel.php");
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
<title>Gestión de Pedidos - Admin MRMP</title>
<link rel="stylesheet" href="admin.css">
<style>
.badge {
    padding: 5px 10px;
    border-radius: 3px;
    color: white;
    font-weight: bold;
    font-size: 12px;
}
.badge-warning { background: #ffc107; color: #000; }
.badge-info { background: #17a2b8; }
.badge-success { background: #28a745; }
.badge-danger { background: #dc3545; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
th { background-color: #f2f2f2; font-weight: bold; }
.btn { padding: 6px 12px; border: none; cursor: pointer; border-radius: 3px; color: white; margin: 2px 0; display: inline-block; }
.btn-primary { background: #007bff; }
.btn-success { background: #28a745; }
.btn-danger { background: #dc3545; }
select { padding: 6px; margin-right: 5px; border-radius: 3px; }
</style>
</head>
<body>

<header>
<h1>Panel de Administración MRMP</h1>
<a href="admin_panel.php" style="color:#ff0000;">
       Panel Admin
</a> | 
<a href="dashboard-piezas.php" style="color:#ff0000;">
    Pagina de Piezas
</a> | 
<a href="?logout" style="color:#ff0000;">
    Cerrar sesión
</a>
</header>

<main>
<?php if($mensaje): ?>
<div class="modal-mensaje exito">
<div class="modal-contenido">
<h2>Mensaje</h2>
<p><?= htmlspecialchars($mensaje) ?></p>
<button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
</div>
</div>
<?php endif; ?>

<section class="formulario">
<h2>Pedidos Registrados</h2>

<table>
<tr>
<th>ID</th>
<th>Usuario</th>
<th>Fecha</th>
<th>Total</th>
<th>Dirección</th>
<th>Estado</th>
<th>Paquetería</th>
<th>Acciones</th>
</tr>
<?php
$pedidos_query = $conexion->query("SELECT p.*, u.nombre as usuario_nombre, u.correo 
                                     FROM pedidos p 
                                     JOIN usuarios u ON p.usuario_id = u.id 
                                     ORDER BY p.fecha DESC");
while($pedido = $pedidos_query->fetch_assoc()):
    // Determinar clase de badge según estado
    $badge_class = '';
    switch($pedido['estado']){
        case 'pendiente': $badge_class = 'badge-warning'; break;
        case 'confirmado': $badge_class = 'badge-info'; break;
        case 'enviado': $badge_class = 'badge-success'; break;
        case 'cancelado': $badge_class = 'badge-danger'; break;
    }
?>
<tr>
<td><?= $pedido['id'] ?></td>
<td>
    <?= htmlspecialchars($pedido['usuario_nombre']) ?><br>
    <small><?= htmlspecialchars($pedido['correo']) ?></small>
</td>
<td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
<td>$<?= number_format($pedido['total'], 2) ?></td>
<td>
    <?= htmlspecialchars($pedido['direccion']) ?>,<br>
    <?= htmlspecialchars($pedido['ciudad']) ?> <?= htmlspecialchars($pedido['codigo_postal']) ?>
</td>
<td><span class="badge <?= $badge_class ?>"><?= ucfirst($pedido['estado']) ?></span></td>
<td><?= $pedido['paqueteria'] ? htmlspecialchars($pedido['paqueteria']) : '-' ?></td>
<td>
    <?php if($pedido['estado'] === 'pendiente'): ?>
        <!-- Confirmar Pedido -->
        <form method="post" action="procesar_estado_pedido.php" style="margin-bottom:8px;">
            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
            <input type="hidden" name="estado" value="confirmado">
            <button type="submit" name="actualizar_estado_pedido" class="btn btn-primary">✅ Confirmar</button>
        </form>
        
        <!-- Marcar como Enviado -->
        <form method="post" action="procesar_estado_pedido.php" style="margin-bottom:8px;">
            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
            <input type="hidden" name="estado" value="enviado">
            <select name="paqueteria" required style="padding:6px; margin-right:5px; border-radius:3px;">
                <option value="">Seleccionar Paquetería</option>
                <option value="Estafeta">Estafeta</option>
                <option value="DHL">DHL</option>
                <option value="FedEx">FedEx</option>
                <option value="Correos de México">Correos de México</option>
            </select>
            <button type="submit" name="actualizar_estado_pedido" class="btn btn-success">📦 Marcar Enviado</button>
        </form>
        
        <!-- Cancelar -->
        <form method="post" action="procesar_estado_pedido.php" onsubmit="return confirm('¿Seguro que deseas cancelar este pedido?');">
            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
            <input type="hidden" name="estado" value="cancelado">
            <button type="submit" name="actualizar_estado_pedido" class="btn btn-danger">❌ Cancelar Pedido</button>
        </form>
    <?php elseif($pedido['estado'] === 'confirmado'): ?>
        <!-- Marcar como Enviado -->
        <form method="post" action="procesar_estado_pedido.php">
            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
            <input type="hidden" name="estado" value="enviado">
            <select name="paqueteria" required style="padding:6px; margin-right:5px; border-radius:3px;">
                <option value="">Seleccionar Paquetería</option>
                <option value="Estafeta">Estafeta</option>
                <option value="DHL">DHL</option>
                <option value="FedEx">FedEx</option>
                <option value="Correos de México">Correos de México</option>
            </select>
            <button type="submit" name="actualizar_estado_pedido" class="btn btn-success">📦 Marcar Enviado</button>
        </form>
    <?php else: ?>
        <em>No disponible</em>
    <?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</table>
</section>

</main>

<footer style="text-align: center; margin-top: 20px; color: #888;">
© <?= date('Y') ?> Mexican Racing Motor Parts
</footer>

</body>
</html>
