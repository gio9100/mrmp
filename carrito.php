<?php
// session_start inicia la "memoria" del servidor para recordar quién es el usuario mientras navega entre páginas
session_start();

// require_once pega el contenido de conexion.php aquí; si falla, detiene todo el código
require_once "conexion.php";

// require_once carga el archivo para enviar correos, necesario para mandar las notificaciones de compra
require_once "enviar_correo.php";

// isset verifica si la palabra 'logout' aparece en la barra de direcciones (URL)
if(isset($_GET['logout'])){
    // session_destroy borra toda la información guardada de la sesión (desconecta al usuario)
    session_destroy();
    
    // header envía una instrucción invisible al navegador para redirigir a 'pagina-principal.php'
    header("Location: pagina-principal.php");
    
    // exit detiene la ejecución del script aquí mismo para que no se procese nada más
    exit;
}

// !isset verifica si NO existe la variable 'carrito' en la sesión del usuario
if(!isset($_SESSION['carrito'])) {
    // Si no existe, creamos el carrito como una lista vacía [] para empezar a guardar productos
    $_SESSION['carrito'] = [];
}

// Inicializamos $mensaje vacío; lo usaremos después para mostrar alertas de éxito o error en la pantalla
$mensaje = "";

// isset verifica si presionaron el botón 'checkout' Y (!empty) revisa que el carrito tenga productos
if(isset($_POST['checkout']) && !empty($_SESSION['carrito'])){
    // $_SESSION accede a la información guardada del usuario logueado actualmente
    $usuario_id = $_SESSION['usuario_id'];
    
    // $_POST captura los datos que el usuario escribió en los campos del formulario HTML
    $direccion = $_POST['direccion'];
    $ciudad = $_POST['ciudad'];
    $postal = $_POST['postal'];
    $telefono = $_POST['telefono'];
    
    // El operador ?? significa: "si 'metodo_pago' no viene, usa 'tarjeta' por defecto"
    $metodo_pago = $_POST['metodo_pago'] ?? 'tarjeta';
    
    // implode convierte la lista de IDs (1, 2, 5) en un texto plano "1,2,5" que SQL sí entiende
    $ids = implode(",", array_keys($_SESSION['carrito']));
    
    // SELECT pide datos. "WHERE id IN ($ids)" busca solamente las piezas que coincidan con esa lista de IDs
    $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
    
    // -> (flecha) se usa para dar órdenes al objeto $conexion. query ejecuta la consulta SQL
    $resultado = $conexion->query($sql);
    
    $total = 0; // Creamos una variable en 0 para ir sumando el precio total
    
    // while es un bucle que se repite mientras haya datos. fetch_assoc convierte cada fila de la DB en una lista con nombres
    while($row = $resultado->fetch_assoc()){
        // Buscamos cuántas unidades de este producto quiere el usuario (lo tenemos en sesión)
        $cant = $_SESSION['carrito'][$row['id']];
        
        // += suma al acumulador. Multiplicamos precio de la pieza * cantidad
        $total += $row['precio'] * $cant;
    }
    
    // INSERT INTO prepara la orden para guardar el pedido nuevo en la tabla 'pedidos'
    // Los signos ? son "huecos de seguridad" que llenaremos después para evitar hackeos (SQL Injection)
    $sql_pedido = "INSERT INTO pedidos (usuario_id, total, direccion, ciudad, codigo_postal, telefono, metodo_pago, estado) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')";
    
    // prepare crea una "declaración preparada" ($stmt). Es como una caja segura donde metemos la consulta antes de enviarla
    $stmt = $conexion->prepare($sql_pedido);
    
    // bind_param rellena esos huecos (?) de forma segura con los datos reales.
    // "idsssss" le dice a PHP qué tipo de dato es cada variable en orden: i=entero (número), d=decimal, s=string (texto)
    $stmt->bind_param("idsssss", $usuario_id, $total, $direccion, $ciudad, $postal, $telefono, $metodo_pago);
    
    // execute da la orden final para guardar la información en la base de datos
    $stmt->execute();
    
    // insert_id recupera el ID numérico que la base de datos le asignó automáticamente a este nuevo pedido
    $pedido_id = $conexion->insert_id;
    
    // close cierra la "caja segura" ($stmt) para liberar memoria del servidor
    $stmt->close();
    
    // data_seek(0) rebobina los resultados de la consulta de piezas al principio para volver a leerlos
    $resultado->data_seek(0);
    
    // Volvemos a recorrer los productos uno por uno para guardar el detalle de cada pieza comprada
    while($row = $resultado->fetch_assoc()){
        $cant = $_SESSION['carrito'][$row['id']]; // Cantidad
        $precio_unitario = $row['precio'];        // Precio
        
        // Preparamos otra consulta segura para insertar en la tabla 'detalle_pedidos'
        $sql_detalle = "INSERT INTO detalle_pedidos (pedido_id, pieza_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
        $stmt_det = $conexion->prepare($sql_detalle);
        
        // Vinculamos: i (ID pedido), i (ID pieza), i (cantidad), d (precio decimal)
        $stmt_det->bind_param("iiid", $pedido_id, $row['id'], $cant, $precio_unitario);
        
        $stmt_det->execute(); // Ejecutamos la inserción
        $stmt_det->close();   // Cerramos
    }
    
    // Preparamos el asunto del correo de confirmación
    $asunto_comprador = "Confirmación de Pedido #$pedido_id - MRMP";
    
    // El punto (.) conecta textos. \n crea un salto de línea dentro del correo
    $cuerpo_comprador = "Hola " . $_SESSION['usuario_nombre'] . ",\n\nTu pedido #$pedido_id ha sido confirmado.\n\nTotal: $" . number_format($total, 2) . "\n\nGracias por tu compra.";
    
    // Llamamos a nuestra función personalizada para enviar el email
    enviarCorreo($_SESSION['usuario_correo'], $asunto_comprador, $cuerpo_comprador);
    
    // Pedimos a la base de datos los correos de todos los administradores
    $sql_admins = "SELECT correo FROM admins";
    $result_admins = $conexion->query($sql_admins);
    
    $asunto_admin = "Nuevo Pedido #$pedido_id - MRMP";
    $cuerpo_admin = "Se ha recibido un nuevo pedido.\n\nPedido ID: $pedido_id\nCliente: " . $_SESSION['usuario_nombre'] . "\nTotal: $" . number_format($total, 2);
    
    // Usamos while para enviar un correo separado a cada administrador encontrado
    while($admin = $result_admins->fetch_assoc()) {
        enviarCorreo($admin['correo'], $asunto_admin, $cuerpo_admin);
    }
    
    // Vaciamos el carrito (lo convertimos en lista vacía) porque la compra ya terminó
    $_SESSION['carrito'] = [];
    
    // Guardamos un mensaje de éxito para que el usuario sepa que todo salió bien
    $mensaje = "¡Pedido realizado con éxito! Recibirás un correo de confirmación.";
}

// isset verifica si en la URL viene el parámetro 'agregar' (ej: carrito.php?agregar=5)
if(isset($_GET['agregar'])){
    // intval limpia cualquier basura y asegura que el ID sea solo un número entero
    $id = intval($_GET['agregar']);
    
    // Si el producto aún no está en el carrito, lo creamos empezando con cantidad 0
    if(!isset($_SESSION['carrito'][$id])) $_SESSION['carrito'][$id] = 0;
    
    // ++ suma 1 a la cantidad actual de ese producto
    $_SESSION['carrito'][$id]++;
    
    // Redirigimos a la misma página para limpiar la URL y mostrar el cambio visualmente
    header("Location: carrito.php");
    exit; // Fin del script
}

// isset verifica si quieren eliminar un producto (ej: carrito.php?eliminar=5)
if(isset($_GET['eliminar'])){
    $id = intval($_GET['eliminar']);
    
    // unset destruye una variable específica. Aquí borra ese producto de la lista del carrito
    unset($_SESSION['carrito'][$id]);
    
    header("Location: carrito.php"); // Recargamos
    exit;
}

// Verificamos si enviaron el formulario de actualización de cantidades (POST)
if(isset($_POST['cantidad'])){
    // foreach recorre uno por uno los elementos de una lista
    // $id es la clave (ID del producto), $cant es el valor (nueva cantidad)
    foreach($_POST['cantidad'] as $id => $cant){
        // Si pide más de 0, actualizamos el valor asegurando que sea entero
        if($cant > 0) $_SESSION['carrito'][$id] = intval($cant);
        // Si pone 0 o negativo, eliminamos el producto del carrito
        else unset($_SESSION['carrito'][$id]);
    }
    $mensaje = "Carrito actualizado";
}
?>
<!-- DOCTYPE le dice al navegador que este es un documento HTML5 moderno -->
<!DOCTYPE html>
<!-- lang="es" configura e idioma de la página como español -->
<html lang="es">
<head>
    <!-- meta charset asegura que las tildes, eñes y caracteres especiales se vean bien -->
    <meta charset="UTF-8">
    <!-- meta viewport ajusta el ancho de la página al ancho de la pantalla para móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito - MRMP</title>
    
    <!-- EXPLICACIÓN DE RECURSOS EXTERNOS (CDN): -->
    <!-- Google Fonts: Conecta con Google para usar letras "Roboto" y "Poppins" en lugar de las aburridas por defecto. -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS (CDN): 
         Este archivo es el "maquillaje" profesional de tu sitio. Contiene miles de reglas de diseño (CSS) ya escritas.
         ¿Cómo funciona? Funciona detectando las "clases" que pones en tu HTML:
         - Botones: Cuando escribes class="btn btn-primary", este archivo le dice al navegador: "Pintalo de azul, ponle bordes redondos, letra blanca y cambia de color al pasar el mouse".
         - Columnas: Cuando pones class="col-md-6", este archivo le dice: "Usa exactamente el 50% del ancho de la pantalla y ponte al lado del otro elemento".
         Sin este enlace, todas esas clases (btn, col, card, alert) no harían nada y tu web se vería como texto plano y feo. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome: Librería que nos deja poner iconos como el carrito de compras usando código. -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- main.css: Tus estilos propios para dar el toque final a la web. -->
    <link href="main.css" rel="stylesheet">
</head>
<body class="mrmp-home">

    <!-- navbar: Crea la barra de menú. navbar-expand-lg: Se ve completa en PC y se colapsa en móvil. navbar-dark bg-dark: Letras blancas con fondo negro -->
    <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <!-- container: Centra el contenido y le da márgenes laterales automáticos (es la caja principal de bootstrap) -->
        <div class="container">
            <!-- navbar-brand: Clase especial para el logo o nombre de la marca en la barra -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- navbar-toggler: Es el botón "hamburguesa" que aparece solo en móviles para abrir el menú -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- collapse navbar-collapse: Todo lo que esté aquí dentro se ocultará en móviles dentro del botón hamburguesa -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- navbar-nav: Lista de items del menú. me-auto: "Margin End Auto", empuja todo el contenido restante a la derecha -->
                <ul class="navbar-nav me-auto">
                    <!-- nav-item / nav-link: Estilos para cada botón del menú -->
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog.php"><i class="fas fa-blog me-1"></i>Blog</a></li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- dropdown: Clase que activa un submenú desplegable al hacer clic -->
                        <div class="nav-item dropdown">
                            <!-- dropdown-toggle: Añade la flechita que indica que hay más opciones -->
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <!-- dropdown-menu: La cajita con las opciones que aparece al hacer clic -->
                            <ul class="dropdown-menu">
                                <!-- dropdown-item: Cada opción del submenú -->
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item active" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <!-- dropdown-divider: Una línea fina para separar opciones -->
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

    <main style="padding-top: 100px; padding-bottom: 50px; min-height: calc(100vh - 150px);">
        <div class="container">
            <!-- text-center: Centra el texto. mb-5: "Margin Bottom 5", añade un espacio grande abajo -->
            <div class="text-center mb-5">
                <!-- display-4: Título gigante y estilizado de Bootstrap. text-white: Color de texto blanco -->
                <h1 class="display-4 mb-2 text-white">
                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                    Tu Carrito de Cotización
                </h1>
                <!-- lead: Hace que el párrafo se vea un poco más grande y destacado que el texto normal -->
                <p class="lead" style="color: #b0b0b0;">Revisa y gestiona tus piezas seleccionadas</p>
            </div>

            <?php if($mensaje): ?>
            <!-- alert alert-success: Crea una cajita verde de confirmación -->
            <div class="alert alert-success text-center mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
            </div>
            <?php endif; ?>

            <?php if(empty($_SESSION['carrito'])): ?>
            <!-- card: Contenedor con borde tipo tarjeta. mx-auto: Centra la tarjeta horizontalmente -->
            <div class="card text-center mx-auto bg-dark text-white border-secondary" style="max-width: 500px;">
                <!-- card-body: El área de contenido dentro de la tarjeta. p-5: "Padding 5", mucho espacio interno -->
                <div class="card-body p-5">
                    <i class="fas fa-shopping-cart mb-4" style="font-size: 4rem; color: #666;"></i>
                    <h2 class="h4 mb-3">El carrito está vacío</h2>
                    <p class="mb-4 text-muted">No has agregado ninguna pieza todavía</p>
                    <!-- btn btn-primary: Botón azul estándar de Bootstrap -->
                    <a href="dashboard-piezas.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Buscar Piezas
                    </a>
                </div>
            </div>
            
            <?php else: ?>
                <form method="post" action="carrito.php">
                    <!-- table-responsive: Hace que si la tabla es muy ancha, tenga su propio scroll horizontal en celulares -->
                    <div class="table-responsive mb-4">
                        <!-- table: Estilos base de tabla. table-dark: Estilo oscuro. table-hover: Ilumina la fila donde pones el mouse -->
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
                                // PHP para obtener y mostrar los productos del carrito
                                
                                // implode convierte la lista de IDs a texto plano para la base de datos
                                $ids = implode(",", array_keys($_SESSION['carrito']));
                                
                                // Obtenemos solo las piezas que están en el carrito
                                $sql = "SELECT * FROM piezas WHERE id IN ($ids)";
                                $resultado = $conexion->query($sql);
                                $total = 0;
                                
                                // Repetimos este bloque por cada producto encontrado
                                while($row = $resultado->fetch_assoc()):
                                    // Calculamos el subtotal (precio x cantidad)
                                    $cant = $_SESSION['carrito'][$row['id']];
                                    $subtotal = $row['precio'] * $cant;
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td>
                                        <!-- d-flex: "Display Flex", pone los elementos uno al lado del otro. align-items-center: Los centra verticalmente -->
                                        <div class="d-flex align-items-center">
                                            <?php if($row['imagen']): ?>
                                                <!-- rounded: Bordes redondeados. me-3: margin-end-3 (margen a la derecha) -->
                                                <img src="uploads/<?= htmlspecialchars($row['imagen']) ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-white-50"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <!-- Imprimimos el nombre del producto de forma segura -->
                                                <h6 class="mb-0 text-white"><?= htmlspecialchars($row['nombre']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($row['marca_nombre'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-white">$<?= number_format($row['precio'], 2) ?></td>
                                    <td>
                                        <!-- form-control: Estilo base para inputs. form-control-sm: Versión pequeña del input -->
                                        <input type="number" name="cantidad[<?= $row['id'] ?>]" value="<?= $cant ?>" min="1" max="<?= $row['cantidad'] ?>" class="form-control form-control-sm bg-dark text-white border-secondary">
                                    </td>
                                    <!-- fw-bold: "Font Weight Bold", pone el texto en negrita -->
                                    <td class="text-white fw-bold">$<?= number_format($subtotal, 2) ?></td>
                                    <td>
                                        <!-- btn-outline-danger: Botón transparente con borde rojo que se llena al pasar el mouse -->
                                        <a href="carrito.php?eliminar=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <!-- text-end: Alinea el texto a la derecha -->
                                    <td colspan="3" class="text-end text-white h5">Total:</td>
                                    <td colspan="2" class="text-white h5">$<?= number_format($total, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- justify-content-between: Separa los botones a los extremos opuestos de la caja -->
                    <div class="d-flex justify-content-between mb-5">
                        <a href="dashboard-piezas.php" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Seguir Comprando
                        </a>
                        <button type="submit" class="btn btn-info text-white">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar Carrito
                        </button>
                    </div>
                </form>

                <!-- shadow-lg: Añade una sombra grande y difuminada alrededor de la tarjeta -->
                <div class="card bg-dark text-white border-secondary shadow-lg">
                    <!-- card-header: Encabezado visual de la tarjeta -->
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Finalizar Compra</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="carrito.php">
                            <input type="hidden" name="checkout" value="1">
                            
                            <!-- row g-3: Crea una fila (sistema de rejilla) con un espacio de separación de 3 entre columnas -->
                            <div class="row g-3">
                                <!-- col-md-6: En pantallas medianas (PC/Tablet), esta columna ocupará la mitad (6 de 12) del ancho -->
                                <div class="col-md-6">
                                    <label class="form-label">Dirección de Envío</label>
                                    <input type="text" name="direccion" class="form-control bg-secondary text-white border-0" required placeholder="Calle, Número, Colonia">
                                </div>
                                <!-- col-md-3: Ocupará un cuarto (3 de 12) del ancho -->
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
                                    <!-- form-select: Estilo especial de Bootstrap para los menús desplegables -->
                                    <select name="metodo_pago" class="form-select bg-secondary text-white border-0">
                                        <option value="tarjeta">Tarjeta de Crédito/Débito</option>
                                        <option value="paypal">PayPal</option>
                                        <option value="transferencia">Transferencia Bancaria</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- border-top: Añade una línea en la parte superior del div -->
                            <div class="mt-4 pt-3 border-top border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0 text-muted small"><i class="fas fa-lock me-1"></i>Pago seguro y encriptado</p>
                                    </div>
                                    <!-- btn-success: Botón de color verde para acciones positivas/confirmar. btn-lg: Botón grande -->
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

    <!-- mt-auto: Si la página es corta, empuja el footer hasta el fondo de la ventana -->
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
    
    <!-- Bootstrap Bundle JS:
         Este archivo es el "cerebro" o el "músculo" del sitio. Mientras que el CSS hace que se vea bonito, este Script hace que las cosas SE MUEVAN.
         ¿Qué hace realmente?
         - Menú Móvil: Cuando picas el botón hamburguesa (navbar-toggler), este script escucha el clic y agrega una clase para mostrar/ocultar el menú.
         - Desplegables: Cuando picas en tu nombre de usuario, este script calcula dónde abrir la cajita del menú y la muestra.
         - Cierres: Permite cerrar alertas o ventanas modales con la X.
         Sin este archivo, los botones se verían bonitos pero al hacerles clic NO PASARÍA NADA. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>