<?php
session_start();
require_once "conexion.php";
require_once "enviar_correo.php";

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: pagina-principal.php");
    exit;
}

// Initialize cart
if(!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

$mensaje = "";

// Process checkout
if(isset($_POST['checkout']) && !empty($_SESSION['carrito'])){
    $usuario_id = $_SESSION['usuario_id'];
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $postal = $_POST['postal'];
    $telefono = $_POST['telefono'];
    $metodo_pago = $_POST['metodo_pago'] ?? 'tarjeta';
    
    // Calculate total
    $ids = implode(",", array_keys($_SESSION['carrito']));
    $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
    $resultado = $conexion->query($sql);
    $total = 0;
    while($row = $resultado->fetch_assoc()){
        $cant = $_SESSION['carrito'][$row['id']];
        $total += $row['precio'] * $cant;
    }
    
    // Insert order
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, direccion, ciudad, codigo_postal, telefono, metodo_pago, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
    $stmt = $conexion->prepare($sql_pedido);
    $stmt->bind_param("idsssss", $usuario_id, $total, $direccion, $ciudad, $postal, $telefono, $metodo_pago);
    $stmt->execute();
    $pedido_id = $conexion->insert_id;
    $stmt->close();
    
    // Insert order details
    $resultado->data_seek(0);
    while($row = $resultado->fetch_assoc()){
        $cant = $_SESSION['carrito'][$row['id']];
        $precio_unitario = $row['precio'];
        $sql_detalle = "INSERT INTO detalle_pedidos (pedido_id, pieza_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_det = $conexion->prepare($sql_detalle);
        $stmt_det->bind_param("iiid", $pedido_id, $row['id'], $cant, $precio_unitario);
        $stmt_det->execute();
        $stmt_det->close();
    }
    
    // Send confirmation email to buyer
    $asunto_comprador = "Confirmación de Pedido #$pedido_id - MRMP";
    $cuerpo_comprador = "Hola " . $_SESSION['usuario_nombre'] . ",\n\nTu pedido #$pedido_id ha sido confirmado.\n\nTotal: $" . number_format($total, 2) . "\n\nGracias por tu compra.";
    enviarCorreo($_SESSION['usuario_correo'], $asunto_comprador, $cuerpo_comprador);
    
    // Get all admin emails and send notification to each
    $sql_admins = "SELECT correo FROM admins";
    $result_admins = $conexion->query($sql_admins);
    
    $asunto_admin = "Nuevo Pedido #$pedido_id - MRMP";
    $cuerpo_admin = "Se ha recibido un nuevo pedido.\n\nPedido ID: $pedido_id\nCliente: " . $_SESSION['usuario_nombre'] . "\nTotal: $" . number_format($total, 2);
    
    while($admin = $result_admins->fetch_assoc()) {
        enviarCorreo($admin['correo'], $asunto_admin, $cuerpo_admin);
    }
    
    $_SESSION['carrito'] = [];
    $mensaje = "¡Pedido realizado con éxito! Recibirás un correo de confirmación.";
}

// Add to cart
if(isset($_GET['agregar'])){
    $id = intval($_GET['agregar']);
    if(!isset($_SESSION['carrito'][$id])) $_SESSION['carrito'][$id] = 0;
    $_SESSION['carrito'][$id]++;
    header("Location: carrito.php");
    exit;
}

// Remove from cart
if(isset($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    unset($_SESSION['carrito'][$id]);
    header("Location: carrito.php");
    exit;
}

// Update quantities
if(isset($_POST['cantidad'])){
    foreach($_POST['cantidad'] as $id => $cant){
        if($cant > 0) $_SESSION['carrito'][$id] = intval($cant);
        else unset($_SESSION['carrito'][$id]);
    }
    $mensaje = "Carrito actualizado";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - MRMP</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
</head>
<body class="mrmp-home">

    <!-- Header -->
    <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog.php"><i class="fas fa-blog me-1"></i>Blog</a></li>
                </ul>
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item active" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main style="padding-top: 100px; padding-bottom: 50px; min-height: calc(100vh - 150px);">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="display-4 mb-2 text-white">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    Tu Carrito de Cotización
                </h1>
                <p class="lead" style="color: #b0b0b0;">Revisa y gestiona tus piezas seleccionadas</p>
            </div>

            <?php if($mensaje): ?>
            <div class="alert alert-success text-center mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>

            <?php if(empty($_SESSION['carrito'])): ?>
            <div class="card text-center mx-auto bg-dark text-white border-secondary" style="max-width: 500px;">
                <div class="card-body p-5">
                    <i class="fas fa-shopping-cart mb-4" style="font-size: 4rem; color: #666;"></i>
                    <h2 class="h4 mb-3">El carrito está vacío</h2>
                    <p class="mb-4 text-muted">No has agregado ninguna pieza todavía</p>
                    <a href="dashboard-piezas.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Buscar Piezas
                    </a>
                </div>
            </div>
            <?php else: ?>
                <form method="post" action="carrito.php">
                    <div class="table-responsive mb-4">
                        <table class="table table-dark table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th style="width: 150px;">Cantidad</th>
                                    <th>Subtotal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ids = implode(",", array_keys($_SESSION['carrito']));
                                $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
                                $resultado = $conexion->query($sql);
                                $total = 0;
                                
                                while($row = $resultado->fetch_assoc()):
                                    $cant = $_SESSION['carrito'][$row['id']];
                                    $subtotal = $row['precio'] * $cant;
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if($row['imagen']): ?>
                                                <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-white-50"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0 text-white"><?= htmlspecialchars($row['nombre']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($row['marca_nombre'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-white">$<?= number_format($row['precio'], 2) ?></td>
                                    <td>
                                        <input type="number" name="cantidad[<?= $row['id'] ?>]" value="<?= $cant ?>" min="1" max="<?= $row['cantidad'] ?>" class="form-control form-control-sm bg-dark text-white border-secondary">
                                    </td>
                                    <td class="text-white fw-bold">$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <a href="carrito.php?eliminar=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end text-white h5">Total:</td>
                                    <td colspan="2" class="text-white h5">$<?= number_format($total, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-5">
                        <a href="dashboard-piezas.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                        </a>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar Carrito
                        </button>
                    </div>
                </form>

                <!-- Checkout Form -->
                <div class="card bg-dark text-white border-secondary shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Finalizar Compra</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="carrito.php">
                            <input type="hidden" name="checkout" value="1">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Dirección de Envío</label>
                                    <input type="text" name="direccion" class="form-control bg-secondary text-white border-0" required placeholder="Calle, Número, Colonia">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Ciudad</label>
                                    <input type="text" name="ciudad" class="form-control bg-secondary text-white border-0" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Código Postal</label>
                                    <input type="text" name="postal" class="form-control bg-secondary text-white border-0" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control bg-secondary text-white border-0" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Método de Pago</label>
                                    <select name="metodo_pago" class="form-select bg-secondary text-white border-0">
                                        <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="transferencia">Transferencia Bancaria</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 text-muted small"><i class="fas fa-lock me-1"></i>Pago seguro y encriptado</p>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-check-circle me-2"></i>Confirmar Pedido
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto border-top border-danger">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Mexican Racing Motor Parts</h5>
                    <p class="mb-0">Tu aliado confiable en piezas automotrices de alto desempeño</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-white me-3">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="text-white me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <p class="mt-2 mb-0">&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>