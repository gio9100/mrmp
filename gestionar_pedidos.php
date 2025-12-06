<?php
// Inicia sesión para verificar administrador
session_start();
// Incluye archivo de conexión a la base de datos
require_once "conexion.php";

// Si no existe la sesión de administrador
if(!isset($_SESSION['admin_id'])){
    // Redirige al panel de inicio de sesión de admin
    header("Location: admin_panel.php");
    exit; // Detiene la ejecución
}

// Obtiene mensaje de sesión si existe, o cadena vacía si no
$mensaje = $_SESSION['mensaje'] ?? '';
// Elimina el mensaje de la sesión para que no se muestre de nuevo
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"> <!-- Codificación de caracteres -->
<meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Escala móvil -->
<title>Gestión de Pedidos - Admin MRMP</title>
<link rel="stylesheet" href="admin.css"> <!-- Estilos del panel admin -->
<style>
/* Estilos específicos para esta página */
.badge {
    padding: 5px 10px; /* Espaciado interno */
    border-radius: 3px; /* Bordes redondeados */
    color: white; /* Texto blanco */
    font-weight: bold; /* Texto en negrita */
    font-size: 12px; /* Tamaño de letra pequeño */
}
/* Colores según el estado del pedido */
.badge-warning { background: #ffc107; color: #000; } /* Pendiente: Amarillo */
.badge-info { background: #17a2b8; } /* Confirmado: Azul claro */
.badge-success { background: #28a745; } /* Enviado: Verde */
.badge-danger { background: #dc3545; } /* Cancelado: Rojo */

table { width: 100%; border-collapse: collapse; margin: 20px 0; } /* Tabla ocupa todo ancho */
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; } /* Celdas con borde inferior */
th { background-color: #f2f2f2; font-weight: bold; } /* Encabezados gris claro */

.btn { 
    padding: 6px 12px; /* Tamaño botón */
    border: none; /* Sin borde */
    cursor: pointer; /* Cursor mano */
    border-radius: 3px; /* Bordes redondeados */
    color: white; /* Texto blanco */
    margin: 2px 0; /* Margen vertical */
    display: inline-block; /* Comportamiento en línea */
}
.btn-primary { background: #007bff; } /* Botón azul */
.btn-success { background: #28a745; } /* Botón verde */
.btn-danger { background: #dc3545; } /* Botón rojo */
select { padding: 6px; margin-right: 5px; border-radius: 3px; } /* Estilo para selectores */
</style>
</head>
<body>

<header>
<!-- Título principal -->
<h1>Panel de Administración MRMP</h1>
<!-- Enlaces de navegación rápida -->
<a href="admin_panel.php" style="color:#ff0000;">
       Panel Admin
</a> | 
<a href="dashboard-piezas.php" style="color:#ff0000;">
    Pagina de Piezas
</a> | 
<!-- Enlace para cerrar sesión con parámetro GET -->
<a href="?logout" style="color:#ff0000;">
    Cerrar sesión
</a>
</header>

<main>
<!-- Si hay mensaje de retroalimentación -->
<?php if($mensaje): ?>
<div class="modal-mensaje exito">
    <div class="modal-contenido">
    <h2>Mensaje</h2>
    <p><?= htmlspecialchars($mensaje) ?></p> <!-- Muestra mensaje seguro -->
    <!-- Botón JS para cerrar el mensaje -->
    <button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
    </div>
</div>
<?php endif; ?>

<section class="formulario">
<h2>Pedidos Registrados</h2>

<!-- Tabla de datos -->
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
// Consulta SQL compleja: Une pedidos y usuarios
$pedidos_query = $conexion->query("SELECT p.*, u.nombre as usuario_nombre, u.correo 
                                     FROM pedidos p 
                                     JOIN usuarios u ON p.usuario_id = u.id 
                                     ORDER BY p.fecha DESC");
// Itera sobre cada pedido encontrado
while($pedido = $pedidos_query->fetch_assoc()):
    // Determina clase CSS del badge según estado
    $badge_class = '';
    switch($pedido['estado']){
        case 'pendiente': $badge_class = 'badge-warning'; break;
        case 'confirmado': $badge_class = 'badge-info'; break;
        case 'enviado': $badge_class = 'badge-success'; break;
        case 'cancelado': $badge_class = 'badge-danger'; break;
    }
?>
<tr>
    <!-- Datos simples del pedido -->
    <td><?= $pedido['id'] ?></td>
    <td>
        <?= htmlspecialchars($pedido['usuario_nombre']) ?><br>
        <small><?= htmlspecialchars($pedido['correo']) ?></small>
    </td>
    <!-- Formato de fecha legible -->
    <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
    <!-- Formato moneda -->
    <td>$<?= number_format($pedido['total'], 2) ?></td>
    <td>
        <?= htmlspecialchars($pedido['direccion']) ?>,<br>
        <?= htmlspecialchars($pedido['ciudad']) ?> <?= htmlspecialchars($pedido['codigo_postal']) ?>
    </td>
    <!-- Badge con estado -->
    <td><span class="badge <?= $badge_class ?>"><?= ucfirst($pedido['estado']) ?></span></td>
    <!-- Muestra paquetería o guión si no hay -->
    <td><?= $pedido['paqueteria'] ? htmlspecialchars($pedido['paqueteria']) : '-' ?></td>
    
    <!-- Columna de acciones dinámicas -->
    <td>
        <?php if($pedido['estado'] === 'pendiente'): ?>
            <!-- Opción 1: Confirmar Pedido -->
            <form method="post" action="procesar_estado_pedido.php" style="margin-bottom:8px;">
                <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                <input type="hidden" name="estado" value="confirmado">
                <button type="submit" name="actualizar_estado_pedido" class="btn btn-primary">✅ Confirmar</button>
            </form>
            
            <!-- Opción 2: Marcar como Enviado directa -->
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
            
            <!-- Opción 3: Cancelar -->
            <form method="post" action="procesar_estado_pedido.php" onsubmit="return confirm('¿Seguro que deseas cancelar este pedido?');">
                <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                <input type="hidden" name="estado" value="cancelado">
                <button type="submit" name="actualizar_estado_pedido" class="btn btn-danger">❌ Cancelar Pedido</button>
            </form>

        <?php elseif($pedido['estado'] === 'confirmado'): ?>
            <!-- Si ya está confirmado, solo opción de enviar -->
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
            <!-- Si está cancelado o enviado, no hay acciones -->
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
