<?php
// Iniciamos la sesión para mantener la persistencia de datos del usuario y el carrito entre páginas
session_start();

// Incluimos el archivo de conexión a la base de datos para poder realizar consultas
require_once "conexion.php";

// Incluimos el helper para el envío de correos electrónicos (confirmaciones de pedido, notificaciones)
require_once "enviar_correo.php";

// Lógica de Cerrar Sesión
// Verificamos si se ha recibido el parámetro 'logout' en la URL (método GET)
if(isset($_GET['logout'])){
    // Destruimos todas las variables de sesión activas para cerrar la sesión del usuario
    session_destroy();
    
    // Redirigimos al usuario a la página principal después de cerrar sesión
    header("Location: pagina-principal.php");
    
    // Detenemos la ejecución del script para asegurar que no se procese nada más
    exit;
}

// Inicialización del Carrito
// Si la variable de sesión 'carrito' no existe, la inicializamos como un array vacío para evitar errores
if(!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Variable para almacenar mensajes de retroalimentación al usuario (éxito o error)
$mensaje = "";

// Procesamiento del Checkout (Finalizar Compra)
// Verificamos si se envió el formulario de checkout (POST) y si el carrito no está vacío
if(isset($_POST['checkout']) && !empty($_SESSION['carrito'])){
    // Obtenemos el ID del usuario desde la sesión actual
    $usuario_id = $_SESSION['usuario_id'];
    
    // Recogemos los datos del formulario de envío enviados por POST
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $postal = $_POST['postal'];
    $telefono = $_POST['telefono'];
    
    // Obtenemos el método de pago, asignando 'tarjeta' por defecto si no viene definido (operador null coalescing ??)
    $metodo_pago = $_POST['metodo_pago'] ?? 'tarjeta';
    
    // Calcular el Total del Pedido
    // Extraemos los IDs de los productos del carrito (keys del array) y los unimos con comas para la consulta SQL
    $ids = implode(",", array_keys($_SESSION['carrito']));
    
    // Consultamos la base de datos para obtener los detalles (precio, etc.) de las piezas en el carrito
    // Usamos la cláusula IN para buscar múltiples IDs a la vez
    $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
    $resultado = $conexion->query($sql);
    
    $total = 0; // Inicializamos el acumulador del total
    
    // Iteramos sobre cada producto encontrado en la base de datos
    while($row = $resultado->fetch_assoc()){
        // Obtenemos la cantidad seleccionada por el usuario desde la sesión
        $cant = $_SESSION['carrito'][$row['id']];
        
        // Sumamos al total: precio del producto multiplicado por la cantidad
        $total += $row['precio'] * $cant;
    }
    
    // Insertar el Pedido en la Base de Datos
    // Preparamos la consulta INSERT para la tabla 'pedidos' con marcadores de posición (?) para seguridad
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, direccion, ciudad, codigo_postal, telefono, metodo_pago, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
    $stmt = $conexion->prepare($sql_pedido);
    
    // Vinculamos los parámetros a la consulta preparada
    // 'idsssss' indica los tipos: i (integer), d (double), s (string) para cada variable en orden
    $stmt->bind_param("idsssss", $usuario_id, $total, $direccion, $ciudad, $postal, $telefono, $metodo_pago);
    
    // Ejecutamos la consulta para crear el registro del pedido
    $stmt->execute();
    
    // Obtenemos el ID autogenerado del pedido recién insertado para usarlo en los detalles
    $pedido_id = $conexion->insert_id;
    
    // Cerramos el statement para liberar recursos
    $stmt->close();
    
    // Insertar los Detalles del Pedido (Productos individuales)
    // Reiniciamos el puntero de resultados de la consulta de piezas al inicio (índice 0) para volver a iterar
    $resultado->data_seek(0);
    
    while($row = $resultado->fetch_assoc()){
        $cant = $_SESSION['carrito'][$row['id']]; // Cantidad
        $precio_unitario = $row['precio']; // Precio al momento de la compra
        
        // Preparamos la inserción en la tabla 'detalle_pedidos'
        $sql_detalle = "INSERT INTO detalle_pedidos (pedido_id, pieza_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_det = $conexion->prepare($sql_detalle);
        
        // Vinculamos parámetros: i (int), i (int), i (int), d (double)
        $stmt_det->bind_param("iiid", $pedido_id, $row['id'], $cant, $precio_unitario);
        
        // Ejecutamos la inserción del detalle
        $stmt_det->execute();
        $stmt_det->close();
    }
    
    // Enviar Correo de Confirmación al Comprador
    $asunto_comprador = "Confirmación de Pedido #$pedido_id - MRMP";
    
    // Construimos el cuerpo del mensaje concatenando cadenas y formateando el total con 2 decimales
    $cuerpo_comprador = "Hola " . $_SESSION['usuario_nombre'] . ",\n\nTu pedido #$pedido_id ha sido confirmado.\n\nTotal: $" . number_format($total, 2) . "\n\nGracias por tu compra.";
    
    // Llamamos a la función helper para enviar el correo
    enviarCorreo($_SESSION['usuario_correo'], $asunto_comprador, $cuerpo_comprador);
    
    // Notificar a los Administradores
    // Obtenemos todos los correos de la tabla 'admins'
    $sql_admins = "SELECT correo FROM admins";
    $result_admins = $conexion->query($sql_admins);
    
    $asunto_admin = "Nuevo Pedido #$pedido_id - MRMP";
    $cuerpo_admin = "Se ha recibido un nuevo pedido.\n\nPedido ID: $pedido_id\nCliente: " . $_SESSION['usuario_nombre'] . "\nTotal: $" . number_format($total, 2);
    
    // Iteramos sobre cada administrador y le enviamos la notificación
    while($admin = $result_admins->fetch_assoc()) {
        enviarCorreo($admin['correo'], $asunto_admin, $cuerpo_admin);
    }
    
    // Limpiamos el carrito (array vacío) ya que la compra fue exitosa
    $_SESSION['carrito'] = [];
    
    // Establecemos el mensaje de éxito para mostrar en la vista
    $mensaje = "¡Pedido realizado con éxito! Recibirás un correo de confirmación.";
}

// Agregar al Carrito (Acción GET)
if(isset($_GET['agregar'])){
    // Convertimos el ID a entero (intval) para seguridad y evitar inyecciones
    $id = intval($_GET['agregar']);
    
    // Si el producto no está en el carrito, lo inicializamos con cantidad 0
    if(!isset($_SESSION['carrito'][$id])) $_SESSION['carrito'][$id] = 0;
    
    // Incrementamos la cantidad del producto en 1
    $_SESSION['carrito'][$id]++;
    
    // Redirigimos a la misma página para actualizar la vista y evitar reenvíos de formularios
    header("Location: carrito.php");
    exit;
}

// Eliminar del Carrito (Acción GET)
if(isset($_GET['eliminar'])){
    // Convertimos el ID a entero por seguridad
    $id = intval($_GET['eliminar']);
    
    // Eliminamos el elemento específico del array de sesión usando unset()
    unset($_SESSION['carrito'][$id]);
    
    // Redirigimos para refrescar la vista
    header("Location: carrito.php");
    exit;
}

// Actualizar Cantidades (Acción POST desde el formulario del carrito)
if(isset($_POST['cantidad'])){
    // Iteramos sobre el array de cantidades enviado (ID producto => Nueva Cantidad)
    foreach($_POST['cantidad'] as $id => $cant){
        // Si la cantidad es mayor a 0, actualizamos el valor asegurándonos que sea entero
        if($cant > 0) $_SESSION['carrito'][$id] = intval($cant);
        // Si la cantidad es 0 o menor, eliminamos el producto del carrito
        else unset($_SESSION['carrito'][$id]);
    }
    $mensaje = "Carrito actualizado";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- Configuración del viewport para asegurar que el sitio sea responsive en dispositivos móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - MRMP</title>
    
    <!-- Fuentes de Google: Roboto y Poppins para una tipografía moderna y legible -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS: Framework para el diseño responsive y componentes preestilizados -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome: Librería de iconos vectoriales (carrito, usuario, etc.) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Hoja de estilos personalizada principal del sitio -->
    <link href="main.css" rel="stylesheet">
</head>
<body class="mrmp-home"> <!-- Clase personalizada para estilos específicos de la página -->

    <!-- Header / Barra de Navegación -->
    <!-- navbar-expand-lg: Colapsa en móviles, fixed-top: Fija la barra arriba -->
    <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <!-- Logo y Nombre de la Marca -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- Botón Hamburguesa para móviles -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Enlaces de Navegación -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog.php"><i class="fas fa-blog me-1"></i>Blog</a></li>
                </ul>
                
                <!-- Área de Usuario: Muestra menú si está logueado, o links de acceso si no -->
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- Menú desplegable para usuario autenticado -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <!-- Muestra la cantidad total de items en el carrito sumando los valores del array -->
                                <li><a class="dropdown-item active" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Enlaces para visitantes no autenticados -->
                        <a class="nav-link" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <!-- Estilos inline para asegurar espaciado correcto con el header fijo y el footer -->
    <main style="padding-top: 100px; padding-bottom: 50px; min-height: calc(100vh - 150px);">
        <div class="container">
            <!-- Título de la Página -->
            <div class="text-center mb-5">
                <h1 class="display-4 mb-2 text-white">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    Tu Carrito de Cotización
                </h1>
                <p class="lead" style="color: #b0b0b0;">Revisa y gestiona tus piezas seleccionadas</p>
            </div>

            <!-- Mensaje de Alerta (Éxito/Info) -->
            <?php if($mensaje): ?>
            <div class="alert alert-success text-center mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>

            <!-- Lógica de Visualización del Carrito -->
            <?php if(empty($_SESSION['carrito'])): ?>
            <!-- Estado Vacío: Se muestra si no hay items en el carrito -->
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
                <!-- Formulario para actualizar cantidades -->
                <form method="post" action="carrito.php">
                    <div class="table-responsive mb-4">
                        <!-- Tabla de Productos -->
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
                                // Obtenemos los detalles de los productos en el carrito desde la BD
                                $ids = implode(",", array_keys($_SESSION['carrito']));
                                $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
                                $resultado = $conexion->query($sql);
                                $total = 0;
                                
                                // Iteramos sobre cada producto para mostrarlo en la tabla
                                while($row = $resultado->fetch_assoc()):
                                    $cant = $_SESSION['carrito'][$row['id']];
                                    $subtotal = $row['precio'] * $cant;
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <!-- Imagen del producto: Si existe, la mostramos; si no, un placeholder -->
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
                                        <!-- Input numérico para ajustar la cantidad. Name es un array: cantidad[id_producto] -->
                                        <input type="number" name="cantidad[<?= $row['id'] ?>]" value="<?= $cant ?>" min="1" max="<?= $row['cantidad'] ?>" class="form-control form-control-sm bg-dark text-white border-secondary">
                                    </td>
                                    <td class="text-white fw-bold">$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <!-- Botón para eliminar el producto individualmente -->
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
                    
                    <!-- Botones de Acción del Carrito -->
                    <div class="d-flex justify-content-between mb-5">
                        <a href="dashboard-piezas.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                        </a>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar Carrito
                        </button>
                    </div>
                </form>

                <!-- Formulario de Checkout (Finalizar Compra) -->
                <div class="card bg-dark text-white border-secondary shadow-lg">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Finalizar Compra</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="carrito.php">
                            <!-- Input oculto para identificar que se está enviando el checkout -->
                            <input type="hidden" name="checkout" value="1">
                            
                            <div class="row g-3">
                                <!-- Campos de Dirección y Contacto -->
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
                            
                            <!-- Botón de Confirmación Final -->
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

    <!-- Footer / Pie de Página -->
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

    <!-- Bootstrap Bundle JS (incluye Popper) para funcionalidad de componentes interactivos -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>