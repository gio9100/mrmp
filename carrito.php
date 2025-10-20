<?php
session_start();
require_once "conexion.php";

if(!isset($_SESSION['usuario_id'])){
    header("Location: dashboard-piezas.php");
    exit;
}

if(!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];
$modal_mensaje = "";

// =========================
// AGREGAR AL CARRITO (FICTICIO)
// =========================
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    $id_pieza = intval($_GET['agregar']);
    
    // Obtener información de la pieza
    $stmt = $conexion->prepare("SELECT nombre, cantidad FROM piezas WHERE id=?");
    $stmt->bind_param("i", $id_pieza);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($pieza = $res->fetch_assoc()){
        $cantidad_en_carrito = $_SESSION['carrito'][$id_pieza] ?? 0;
        
        // Validar que no exceda el stock disponible
        if($cantidad_en_carrito < $pieza['cantidad']){
            $_SESSION['carrito'][$id_pieza] = $cantidad_en_carrito + 1;
        } else {
            $modal_mensaje = "⚠️ No hay más stock disponible de '{$pieza['nombre']}'. Stock máximo: {$pieza['cantidad']} unidades.";
        }
    }
    $stmt->close();
    
    if(empty($modal_mensaje)){
        header("Location: carrito.php");
        exit;
    }
}

// =========================
// ELIMINAR DEL CARRITO (FICTICIO)
// =========================
if(isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    if(isset($_SESSION['carrito'][$id])){
        unset($_SESSION['carrito'][$id]);
    }
    header("Location: carrito.php");
    exit;
}

// =========================
// ACTUALIZAR CANTIDADES (FICTICIO)
// =========================
if(isset($_POST['cantidad'])){
    foreach($_POST['cantidad'] as $id => $nueva_cantidad){
        $id = intval($id);
        $nueva_cantidad = intval($nueva_cantidad);
        
        // Obtener stock real
        $stmt = $conexion->prepare("SELECT nombre, cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pieza = $res->fetch_assoc();
        $stmt->close();
        
        // Validar contra stock máximo
        if($nueva_cantidad <= 0){
            unset($_SESSION['carrito'][$id]);
        } elseif($nueva_cantidad <= $pieza['cantidad']){
            $_SESSION['carrito'][$id] = $nueva_cantidad;
        } else {
            $modal_mensaje = "⚠️ No hay suficiente stock de '{$pieza['nombre']}'. Máximo disponible: {$pieza['cantidad']} unidades.";
            $_SESSION['carrito'][$id] = $pieza['cantidad']; // Ajustar al máximo disponible
        }
    }
    
    if(empty($modal_mensaje)){
        header("Location: carrito.php");
        exit;
    }
}

// =========================
// PROCESAR PEDIDO (SOLO GUARDAR EN BD)
// =========================
if(isset($_POST['procesar_pedido'])){
    if(!empty($_SESSION['carrito'])){
        $usuario_id = $_SESSION['usuario_id'];
        $detalles_pedido = json_encode($_SESSION['carrito']);
        $fecha = date('Y-m-d H:i:s');
        
        // Guardar pedido en la base de datos (solo los detalles)
        $stmt = $conexion->prepare("INSERT INTO pedidos (usuario_id, detalles_pedido, fecha, estado) VALUES (?, ?, ?, 'pendiente')");
        $stmt->bind_param("iss", $usuario_id, $detalles_pedido, $fecha);
        
        if($stmt->execute()){
            // Limpiar carrito después de guardar el pedido
            $_SESSION['carrito'] = [];
            $modal_mensaje = "✅ Pedido realizado correctamente. Te contactaremos pronto para confirmar disponibilidad y precios.";
        } else {
            $modal_mensaje = "❌ Error al procesar el pedido. Intenta nuevamente.";
        }
        $stmt->close();
    }
    
    header("Location: carrito.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrito MRMC</title>
<header>
     <div class="logo"><img src="images/mrmp logo.png" alt="mrmp logo" width="100px"></div>
</header>
<style>
body {
    background: linear-gradient(180deg,#0d0d0d 0%,#1a1a1a 100%);
    font-family: Arial, Helvetica, sans-serif;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px;
    min-height: 100vh;
}
h2 { color: #00ffe0; margin-bottom: 20px; text-shadow: 0 0 5px #0040ff; }
table { 
    border-collapse: collapse; 
    width: 100%; 
    max-width: 800px; 
    margin-bottom: 20px; 
    box-shadow: 0 0 15px rgba(0,255,224,0.3);
    background: #1a1a1a;
}
th, td { 
    padding: 12px; 
    text-align: center; 
    border-bottom: 1px solid #0040ff; 
}
th { 
    background: #0040ff; 
    color: #fff; 
}
.cantidad-input { 
    width: 60px; 
    padding: 5px; 
    border-radius: 5px; 
    border: 1px solid #00ffe0; 
    text-align: center; 
    background: #2a2a2a; 
    color: #fff; 
}
.cantidad-input:invalid {
    border-color: #ff4444;
    background: #3a2a2a;
}
.stock-info {
    font-size: 0.9em;
    color: #00ffe0;
}
.acciones {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}
.btn-primary {
    background: #0040ff;
    color: #fff;
}
.btn-primary:hover {
    background: #00ffe0;
    color: #000;
    transform: translateY(-2px);
}
.btn-success {
    background: #00aa00;
    color: #fff;
}
.btn-success:hover {
    background: #00ff00;
    color: #000;
    transform: translateY(-2px);
}
.btn-danger {
    background: #aa0000;
    color: #fff;
}
.btn-danger:hover {
    background: #ff4444;
    transform: translateY(-2px);
}
.btn-secondary {
    background: #666;
    color: #fff;
}
.btn-secondary:hover {
    background: #999;
    transform: translateY(-2px);
}

/* Modal */
.modal-mensaje {
    position: fixed;
    top: 50%; 
    left: 50%;
    transform: translate(-50%, -50%);
    background: #00ffe0;
    color: #000;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.5);
    font-weight: bold;
    z-index: 1000;
    display: none;
    text-align: center;
    max-width: 400px;
    width: 90%;
}
.modal-mensaje.show { display: block; }

.overlay {
    position: fixed;
    top: 0; 
    left: 0;
    width: 100%; 
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 999;
    display: none;
}
.overlay.show { display: block; }

.total-section {
    background: #2a2a2a;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    text-align: center;
    box-shadow: 0 0 10px rgba(0,255,224,0.2);
}

@media screen and (max-width: 900px){
    table, th, td { font-size: 14px; }
    .cantidad-input { width: 50px; }
    .acciones { flex-direction: column; }
}
</style>
</head>
<body>

<!-- Overlay para modal -->
<div class="overlay <?= !empty($modal_mensaje) ? 'show' : '' ?>"></div>

<h2>🛒 Tu Carrito de Cotización</h2>

<?php if(!empty($modal_mensaje)): ?>
<div class="modal-mensaje show">
    <?= htmlspecialchars($modal_mensaje) ?>
    <br><br>
    <button class="btn btn-primary" onclick="cerrarModal()">Aceptar</button>
</div>
<script>
function cerrarModal() {
    document.querySelector('.modal-mensaje').classList.remove('show');
    document.querySelector('.overlay').classList.remove('show');
    window.location.href = 'carrito.php';
}

setTimeout(() => {
    if(document.querySelector('.modal-mensaje.show')) {
        cerrarModal();
    }
}, 5000);
</script>
<?php endif; ?>

<?php if(empty($_SESSION['carrito'])): ?>
<div style="text-align: center; padding: 40px;">
    <p style="font-size: 1.2em; margin-bottom: 20px;">El carrito está vacío.</p>
    <a href="dashboard-piezas.php" class="btn btn-primary">← Volver al Dashboard</a>
</div>
<?php else: ?>
<form method="post" id="carritoForm">
<table>
    <tr>
        <th>Pieza</th>
        <th>Cantidad</th>
        <th>Stock Disponible</th>
        <th>Acciones</th>
    </tr>
    <?php
    $total_items = 0;
    foreach($_SESSION['carrito'] as $id => $cant){
        $stmt = $conexion->prepare("SELECT nombre, cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pieza = $res->fetch_assoc();
        $stmt->close();

        $total_items += $cant;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pieza['nombre']) . "</td>";
        echo "<td>
                <input type='number' 
                       class='cantidad-input'
                       name='cantidad[$id]' 
                       min='1' 
                       max='{$pieza['cantidad']}' 
                       value='$cant'
                       onchange='validarCantidad(this, {$pieza['cantidad']})'>
              </td>";
        echo "<td>
                <span class='stock-info'>{$pieza['cantidad']} unidades</span>
              </td>";
        echo "<td class='acciones'>
                <a href='carrito.php?eliminar=$id' class='btn btn-danger' onclick='return confirm(\"¿Eliminar del carrito?\")'>🗑️ Eliminar</a>
              </td>";
        echo "</tr>";
    }
    ?>
</table>

<div class="total-section">
    <h3>Total: <?= $total_items ?> item(s) en el carrito</h3>
    <p style="color: #00ffe0; font-size: 0.9em;">
        ⓘ Este es un carrito de cotización. Los precios y disponibilidad final serán confirmados al procesar tu pedido.
    </p>
</div>

<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
    <button type="submit" class="btn btn-primary">🔄 Actualizar Cantidades</button>
    <button type="submit" name="procesar_pedido" class="btn btn-success" onclick="return confirm('¿Estás seguro de que quieres procesar este pedido?')">
        ✅ Procesar Pedido
    </button>
    <a href="dashboard-piezas.php" class="btn btn-secondary">← Seguir Cotizando</a>
    <a href="carrito.php?vaciar=1" class="btn btn-danger" onclick="return confirm('¿Vaciar todo el carrito?')">
        🗑️ Vaciar Carrito
    </a>
</div>
</form>

<script>
function validarCantidad(input, maxStock) {
    if (input.value > maxStock) {
        alert('No puedes solicitar más de ' + maxStock + ' unidades. Stock disponible: ' + maxStock);
        input.value = maxStock;
        return false;
    }
    if (input.value < 1) {
        input.value = 1;
    }
    return true;
}

// Validar todo el formulario antes de enviar
document.getElementById('carritoForm').addEventListener('submit', function(e) {
    let inputs = this.querySelectorAll('input[type="number"]');
    let valido = true;
    
    inputs.forEach(input => {
        let max = parseInt(input.getAttribute('max'));
        let valor = parseInt(input.value);
        
        if (valor > max) {
            alert('Una o más cantidades exceden el stock disponible. Por favor ajusta las cantidades.');
            input.value = max;
            valido = false;
        }
    });
    
    if (!valido) {
        e.preventDefault();
    }
});
</script>

<?php endif; ?>

</body>
</html>