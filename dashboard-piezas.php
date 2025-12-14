<?php
// session_start(): Inicia una sesión nueva o reanuda la existente.
// Necesitamos sesión para guardar el 'carrito' y mensajes de notificación.
session_start();

// require_once: Incluye la lógica de conexión a la base de datos (conexion.php).
require_once "conexion.php";

// LÓGICA DE LOGOUT
// Si la URL tiene el parámetro ?logout, destruimos la sesión y recargamos.
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}

// INICIALIZACIÓN DE CARRITO
// Si el usuario está logueado pero no tiene carrito, inicializamos el array.
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// --- ACCIÓN: LÓGICA PARA AGREGAR PRODUCTO AL CARRITO ---
// Verificamos si existe el parámetro GET 'agregar' y si es un número válido.
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    
    // VALIDACIÓN DE SESIÓN: Solo usuarios registrados pueden comprar.
    if(!isset($_SESSION['usuario_id'])){
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        // intval(): Convertimos a entero por seguridad.
        $id_pieza = intval($_GET['agregar']);
        
        // CONSULTA DE STOCK: Verificamos cuánto stock real hay en la BD.
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i", $id_pieza);
        $stmt->execute();
        $res = $stmt->get_result();
        
        // Si encontramos la pieza...
        if($res && $res->num_rows==1){
            $pieza = $res->fetch_assoc();
            
            // VALIDACIÓN DE STOCK vs CARRITO
            // Stock disponible = Stock BD - Cantidad ya en carrito.
            // ($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0))
            if(($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0)) > 0){
                // Incrementamos la cantidad en el carrito.
                $_SESSION['carrito'][$id_pieza] = ($_SESSION['carrito'][$id_pieza] ?? 0) + 1;
                $_SESSION['mensaje'] = "✅ Pieza agregada al carrito.";
            } else {
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente.";
            }
        }
    }
    // Redirigimos a la misma página para limpiar el GET y evitar reenvíos.
    header("Location: dashboard-piezas.php");
    exit;
}

// --- LÓGICA DE FILTROS Y BÚSQUEDA ---
// Recogemos parámetros de búsqueda y filtrado de la URL, saneándolos.
$busqueda = trim($_GET['buscar'] ?? '');
$marca_id = intval($_GET['marca'] ?? 0);

// CONSULTA DE MARCAS (Para el filtro de botones)
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// CONSTRUCCIÓN DINÁMICA DE CONSULTA SQL
// Empezamos con la base: seleccionar piezas y nombre de marca.
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";

$condiciones = [];
$params = [];
$tipos = ""; // Cadena para bind_param (ej: "ssi")

// 1. Filtro por Texto (Nombre o Descripción)
if($busqueda !== ''){
    // Usamos paréntesis para agrupar el OR: (nombre LIKE ... OR descripcion LIKE ...)
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%"; // % es el comodín de SQL (contiene).
    $params[] = "%$busqueda%";
    $tipos .= "ss"; // Añadimos dos strings.
}

// 2. Filtro por Marca (ID exacto)
if($marca_id > 0){
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    $tipos .= "i"; // Añadimos un entero.
}

// Si hay condiciones, las unimos con " AND " y las agregamos al SQL.
if(count($condiciones) > 0){
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}

// Ordenamiento: Productos más recientes primero.
$sql .= " ORDER BY p.id DESC";

// Preparamos y ejecutamos la consulta dinámica.
$stmt = $conexion->prepare($sql);
// Si hay parámetros, los vinculamos dinámicamente usando el operador spread (...).
if(count($params) > 0) $stmt->bind_param($tipos,...$params);
$stmt->execute();

// Obtenemos todos los resultados en un array.
$piezas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// MANEJO DE MENSAJE FLASH
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Piezas - Performance Zone MX</title>
    
    <!-- Librerías Externas: Bootstrap 5 y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos CSS Personalizados -->
    <link href="pagina-principal.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    
    <style>
        /* Estilos específicos para el carrusel de imágenes de productos */
        .carousel-item img {
            height: 200px; /* Altura fija para uniformidad */
            object-fit: cover; /* Recorta la imagen para llenar el espacio sin deformar */
            width: 100%;
        }
        /* Botones de navegación del carrusel más visibles */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5); /* Fondo semitransparente oscuro */
            border-radius: 50%;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-white">

    <!-- BARRA DE NAVEGACIÓN -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/nuevologo.jpeg" alt="Performance Zone MX" height="50" class="d-inline-block align-text-top">
                <span class="brand-text text-dark">Performance Zone MX</span>
            </a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menú Colapsable -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="pagina-principal.php"><i class="fas fa-home me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <!-- .active: Indica página actual. -->
                        <a class="nav-link active fw-bold text-primary" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <!-- Lógica condicional de sesión -->
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- Menú Usuario -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="mis_pedidos.php"><i class="fas fa-box-open me-2"></i>Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Lista de Deseos</a></li>
                                
                                <li><a class="dropdown-item" href="carrito.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                    <!-- Badge con cantidad total de items -->
                                    <span class="badge bg-primary rounded-pill ms-1"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Botones Invitado -->
                        <a class="nav-link text-dark" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link text-dark" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- HEADER / HERO -->
    <section class="hero-catalog bg-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold text-dark">Catálogo de Piezas</h1>
                    <p class="lead text-secondary">Encuentra las mejores piezas automotrices de alto desempeño</p>
                </div>
                <!-- Contador de piezas -->
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex align-items-center justify-content-lg-end">
                        <i class="fas fa-cogs fa-3x me-3 text-secondary opacity-50"></i>
                        <div>
                            <h4 class="mb-0 text-dark"><?= count($piezas) ?></h4>
                            <small class="text-secondary">Piezas Disponibles</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="container my-5">
        
        <!-- ALERTAS DE SISTEMA (Mensajes Flash) -->
        <?php if($mensaje): ?>
        <!-- .alert-dismissible: Permite cerrar la alerta con la 'x'. -->
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- SECCIÓN DE FILTROS -->
        <div class="filter-section mb-4 p-4 bg-white rounded shadow-sm">
            <div class="row g-3">
                
                <!-- Búsqueda por Texto -->
                <div class="col-md-6">
                    <form class="buscar-form" method="get">
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar pieza..." value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary text-white" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                
                <!-- Filtrado por Marcas -->
                <div class="col-md-6">
                    <div class="brand-filter">
                        <strong class="d-block mb-2 text-dark">Filtrar por marca:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($marcas as $id=>$nombre): ?>
                                <!-- Estilo dinámico: btn-primary si está activa, btn-outline si no. -->
                                <a href="?marca=<?= $id ?>" class="btn btn-sm <?= $marca_id == $id ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    <?= htmlspecialchars($nombre) ?>
                                </a>
                            <?php endforeach; ?>
                            
                            <!-- Opción para borrar filtro si hay una marca seleccionada -->
                            <?php if($marca_id > 0): ?>
                                <a href="?" class="btn btn-sm btn-link text-danger text-decoration-none"><i class="fas fa-times me-1"></i>Borrar filtro</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRID DE PRODUCTOS -->
        <div class="row g-4">
            
            <!-- CASO: SIN RESULTADOS -->
            <?php if(count($piezas) === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron piezas</h4>
                    <p class="text-secondary">Intenta con otros términos de búsqueda.</p>
                    <a href="?" class="btn btn-outline-primary mt-2">Ver todas las piezas</a>
                </div>
            
            <!-- CASO: MOSTRAR PRODUCTOS -->
            <?php else: ?>
                <?php foreach($piezas as $p): 
                    // PREPARACIÓN DE IMÁGENES
                    // 1. Imagen principal de la tabla 'piezas'.
                    // 2. Imágenes adicionales de tabla 'piezas_imagenes'.
                    $imagenes = [];
                    if(!empty($p['imagen'])) $imagenes[] = $p['imagen'];
                    
                    $res_gal = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=".$p['id']);
                    while($row_gal = $res_gal->fetch_assoc()){ $imagenes[] = $row_gal['imagen']; }
                ?>
                
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 shadow-sm border-0">
                        
                        <!-- ÁREA DE IMAGEN (CARRUSEL) -->
                        <div class="product-image bg-light position-relative">
                            <?php if(count($imagenes) > 0): ?>
                                <!-- Bootstrap Carousel: ID único por producto (carouselPieza<ID>) -->
                                <div id="carouselPieza<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <!-- .active: Solo el primer item debe tener esta clase inicialmente. -->
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                <!-- data-bs-toggle="modal": Abre el lightbox al hacer click. -->
                                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" style="cursor: pointer;" 
                                                     data-bs-toggle="modal" data-bs-target="#lightboxModal<?= $p['id'] ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Controles del carrusel (solo si hay más de 1 imagen) -->
                                    <?php if(count($imagenes) > 1): ?>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPieza<?= $p['id'] ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon"></span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPieza<?= $p['id'] ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon"></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Placeholder si no hay imágenes -->
                                <div class="d-flex align-items-center justify-content-center h-100 py-5">
                                    <i class="fas fa-camera fa-2x text-muted opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- BADGE DE STOCK (Absoluto sobre la imagen) -->
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?> shadow-sm">
                                    <?= $p['cantidad'] > 0 ? 'En Stock: '.intval($p['cantidad']) : 'Agotado' ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- CUERPO DE LA TARJETA -->
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                            <p class="small text-muted mb-2">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            
                            <!-- Precio y Botones alineados al fondo -->
                            <div class="mt-auto">
                                <h4 class="text-primary fw-bold mb-3">$<?= number_format($p['precio'], 2) ?></h4>
                                
                                <div class="d-grid gap-2">
                                    <!-- Botón Detalles: Abre Modal -->
                                    <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalDesc<?= $p['id'] ?>">
                                        <i class="fas fa-eye me-1"></i>Detalles
                                    </button>
                                    
                                    <!-- Botón Agregar: Condicional por sesión -->
                                    <?php if(isset($_SESSION['usuario_id'])): ?>
                                        <a href="?agregar=<?= intval($p['id']) ?>" class="btn btn-primary btn-sm text-white">
                                            <i class="fas fa-cart-plus me-1"></i>Agregar
                                        </a>
                                    <?php else: ?>
                                        <a href="inicio_secion.php" class="btn btn-light btn-sm text-muted border">
                                            Iniciar sesión
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL: DETALLES COMPLETOS DEL PRODUCTO -->
                <div class="modal fade" id="modalDesc<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom-0">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars($p['nombre']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Imagen Destacada en Modal -->
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <?php if(count($imagenes) > 0): ?>
                                            <img src="uploads/<?= htmlspecialchars($imagenes[0]) ?>" class="img-fluid rounded shadow-sm">
                                        <?php endif; ?>
                                    </div>
                                    <!-- Descripción y Precio -->
                                    <div class="col-md-6">
                                        <h4 class="text-primary fw-bold mb-3">$<?= number_format($p['precio'],2) ?></h4>
                                        <!-- nl2br: Convierte saltos de línea de la BD a etiquetas <br> -->
                                        <p class="text-secondary"><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
                                        
                                        <div class="alert alert-light border mt-4">
                                            <small class="text-muted"><i class="fas fa-truck me-1"></i>Envío disponible a todo México</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-top-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MODAL: LIGHTBOX (Imagen en Pantalla Completa) -->
                <?php if(count($imagenes) > 0): ?>
                <div class="modal fade" id="lightboxModal<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <!-- bg-transparent: Solo queremos ver la imagen flotando -->
                        <div class="modal-content bg-transparent border-0">
                            <div class="modal-header border-0">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0 text-center">
                                <img src="uploads/<?= htmlspecialchars($imagenes[0]) ?>" class="img-fluid" style="max-height: 85vh;">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="bg-white text-center py-3 border-top mt-5">
        <p class="mb-0 text-secondary">&copy; <?= date('Y') ?> Performance Zone MX. Todos los derechos reservados.</p>
    </footer>

    <!-- JS LIBS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JS PERSONALIZADO -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-cerrar alertas después de 5 segundos (5000 ms) para mejorar la UX.
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });
        });
    </script>
</body>
</html>