<?php
// session_start(): Inicia una sesión o reanuda la actual.
// Necesario para verificar si el usuario es un administrador ($_SESSION['admin_id']).
session_start();

// require_once: Incluye el archivo de conexión a la base de datos 'conexion.php'.
// Contiene las credenciales y la instancia $conexion (MySQLi).
require_once "conexion.php";

// VERIFICACIÓN DE SEGURIDAD (ADMINISTRADOR)
// Si NO está definida la variable de sesión 'admin_id', redirigimos al login.
// Esto protege la página contra accesos no autorizados.
if(!isset($_SESSION['admin_id'])){
    // header(): Envía un encabezado HTTP para redirigir al navegador.
    header("Location: admin_panel.php");
    exit; // Detiene la ejecución del script inmediatamente.
}

// MANEJO DE MENSAJES FLASH
// Operador de fusión null (??): Si existe $_SESSION['mensaje'], lo guarda en $mensaje, si no, usa cadena vacía.
$mensaje = $_SESSION['mensaje'] ?? '';

// unset(): Borra la variable de sesión para que el mensaje solo se muestre una vez.
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- meta viewport: Controla el diseño en navegadores móviles. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Performance Zone MX</title>
    
    <!-- Enlace a la hoja de estilos externa del panel administrativo -->
    <link rel="stylesheet" href="admin.css">
    
    <!-- CSS INCORPORADO (<style>): Estilos específicos para esta página. -->
    <style>
    /* Estilos globales */
    .badge {
        padding: 5px 10px;
        border-radius: 3px;
        color: white;
        font-weight: bold;
        font-size: 12px;
    }
    /* Clases de utilidad para colores de estado */
    .badge-warning { background: #ffc107; color: #000; } /* Amarillo */
    .badge-info { background: #17a2b8; }    /* Azul Cian */
    .badge-success { background: #28a745; } /* Verde */
    .badge-danger { background: #dc3545; }  /* Rojo */

    /* Estilos de Tabla */
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #f2f2f2; font-weight: bold; }

    /* Estilos de Botones */
    .btn { 
        padding: 6px 12px;
        border: none;
        cursor: pointer;
        border-radius: 3px;
        color: white;
        margin: 2px 0;
        display: inline-block;
    }
    .btn-primary { background: #007bff; } /* Azul */
    .btn-success { background: #28a745; } /* Verde */
    .btn-danger { background: #dc3545; }  /* Rojo */
    
    /* Estilo para los selectores dentro de la tabla */
    select { padding: 6px; margin-right: 5px; border-radius: 3px; }
    </style>
</head>
<body>

<!-- ENCABEZADO (HEADER) -->
<header>
    <div style="display: flex; align-items: center;">
        <img src="img/nuevologo.jpeg" alt="Logo" style="height: 40px; margin-right: 15px;">
        <h1>Performance Zone MX - Gestión de Pedidos</h1>
    </div>
    <!-- Navegación interna del admin -->
    <nav>
        <a href="admin_panel.php" style="color:#ff0000;">Panel Admin</a> | 
        <a href="dashboard-piezas.php" style="color:#ff0000;">Pagina de Piezas</a> | 
        <a href="?logout" style="color:#ff0000;">Cerrar sesión</a>
    </nav>
</header>

<main>
    <!-- NOTIFICACIONES -->
    <!-- Si la variable $mensaje tiene contenido, mostramos el modal. -->
    <?php if($mensaje): ?>
    <div class="modal-mensaje exito">
        <div class="modal-contenido">
            <h2>Mensaje</h2>
            <!-- htmlspecialchars(): Sanea el mensaje para prevenir XSS. -->
            <p><?= htmlspecialchars($mensaje) ?></p>
            <!-- Javascript inline para cerrar el modal. -->
            <button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
        </div>
    </div>
    <?php endif; ?>

    <section class="formulario">
        <h2>Pedidos Registrados</h2>
        
        <!-- TABLA DE PEDIDOS -->
        <!-- Muestra el listado completo de pedidos para gestión. -->
        <table>
        <thead>
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
        </thead>
        <tbody>
            <?php
            // CONSULTA SQL COMPLEXA (JOIN)
            // Seleccionamos todos los campos del pedido (p.*).
            // UNIMOS con la tabla usuarios (u) para obtener nombre y correo.
            // ON p.usuario_id = u.id: La condición de unión.
            // ORDER BY p.fecha DESC: Los pedidos más recientes primero.
            $pedidos_query = $conexion->query("SELECT p.*, u.nombre as usuario_nombre, u.correo 
                                                 FROM pedidos p 
                                                 JOIN usuarios u ON p.usuario_id = u.id 
                                                 ORDER BY p.fecha DESC");
            
            // fetch_assoc(): Itera por cada fila de resultados.
            while($pedido = $pedidos_query->fetch_assoc()):
                // SWITCH: Determina la clase CSS según el estado del pedido.
                $badge_class = '';
                switch($pedido['estado']){
                    case 'pendiente': $badge_class = 'badge-warning'; break;
                    case 'confirmado': $badge_class = 'badge-info'; break;
                    case 'enviado': $badge_class = 'badge-success'; break;
                    case 'cancelado': $badge_class = 'badge-danger'; break;
                }
            ?>
            <tr>
                <td>#<?= $pedido['id'] ?></td>
                <td>
                    <!-- Mostrar nombre y correo del cliente -->
                    <strong><?= htmlspecialchars($pedido['usuario_nombre']) ?></strong><br>
                    <small><?= htmlspecialchars($pedido['correo']) ?></small>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                <td style="color: #28a745; font-weight: bold;">$<?= number_format($pedido['total'], 2) ?></td>
                <td>
                    <?= htmlspecialchars($pedido['direccion']) ?>,<br>
                    <?= htmlspecialchars($pedido['ciudad']) ?> <?= htmlspecialchars($pedido['codigo_postal']) ?>
                </td>
                <td><span class="badge <?= $badge_class ?>"><?= ucfirst($pedido['estado']) ?></span></td>
                <td><?= $pedido['paqueteria'] ? htmlspecialchars($pedido['paqueteria']) : '-' ?></td>
                
                <!-- ACCIONES DE GESTIÓN -->
                <td>
                    <!-- CASO 1: Pedido Pendiente -->
                    <?php if($pedido['estado'] === 'pendiente'): ?>
                        
                        <!-- Formulario: CONFIRMAR PEDIDO -->
                        <form method="post" action="procesar_estado_pedido.php" style="margin-bottom:8px;">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <input type="hidden" name="estado" value="confirmado">
                            <button type="submit" name="actualizar_estado_pedido" class="btn btn-primary">✅ Confirmar</button>
                        </form>
                        
                        <!-- Formulario: MARCAR COMO ENVIADO -->
                        <form method="post" action="procesar_estado_pedido.php" style="margin-bottom:8px;">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <input type="hidden" name="estado" value="enviado">
                            <!-- Select para elegir paquetería antes de enviar -->
                            <select name="paqueteria" required>
                                <option value="">Seleccionar Paquetería</option>
                                <option value="Estafeta">Estafeta</option>
                                <option value="DHL">DHL</option>
                                <option value="FedEx">FedEx</option>
                                <option value="Correos de México">Correos de México</option>
                            </select>
                            <button type="submit" name="actualizar_estado_pedido" class="btn btn-success">📦 Marcar Enviado</button>
                        </form>
                        
                        <!-- Formulario: CANCELAR PEDIDO -->
                        <!-- onsubmit: Confirmación JS antes de enviar. -->
                        <form method="post" action="procesar_estado_pedido.php" onsubmit="return confirm('¿Seguro que deseas cancelar este pedido?');">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <input type="hidden" name="estado" value="cancelado">
                            <button type="submit" name="actualizar_estado_pedido" class="btn btn-danger">❌ Cancelar Pedido</button>
                        </form>
            
                    <!-- CASO 2: Pedido Confirmado -->
                    <?php elseif($pedido['estado'] === 'confirmado'): ?>
                        <!-- Solo opción de Enviar -->
                        <form method="post" action="procesar_estado_pedido.php">
                            <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                            <input type="hidden" name="estado" value="enviado">
                            <select name="paqueteria" required>
                                <option value="">Seleccionar Paquetería</option>
                                <option value="Estafeta">Estafeta</option>
                                <option value="DHL">DHL</option>
                                <option value="FedEx">FedEx</option>
                                <option value="Correos de México">Correos de México</option>
                            </select>
                            <button type="submit" name="actualizar_estado_pedido" class="btn btn-success">📦 Marcar Enviado</button>
                        </form>
                        
                    <!-- OTROS ESTADOS -->
                    <?php else: ?>
                        <em style="color: #6c757d;">No hay acciones disponibles</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        </table>
    </section>
</main>

<footer style="text-align: center; margin-top: 20px; color: #888;">
    © <?= date('Y') ?> Mexican Racing Motor Parts
</footer>

</body>
</html>
