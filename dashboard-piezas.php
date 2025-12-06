<?php
// session_start(): Esta función inicia una nueva sesión o reanuda la existente.
// Sirve para mantener información del usuario (como ID o carrito) disponible entre diferentes páginas.
session_start();

// require_once: Esta sentencia incluye y evalúa el archivo especificado.
// Sirve para conectar a la base de datos.
require_once "conexion.php";

// isset(): Verifica si está definido el parámetro 'logout'.
// Sirve para procesar el cierre de sesión del usuario.
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}

// Lógica para inicializar el carrito de compras.
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// --- LÓGICA: AGREGAR PRODUCTO AL CARRITO ---
// Verifica si existe el parámetro 'agregar' y si es un número válido.
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    
    // Verifica si el usuario NO está logueado.
    if(!isset($_SESSION['usuario_id'])){
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        // intval(): Sanear input a entero.
        $id_pieza = intval($_GET['agregar']);
        
        // Consulta segura para verificar existencia y stock.
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i", $id_pieza);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if($res && $res->num_rows==1){
            $pieza = $res->fetch_assoc();
            
            // Verificar stock disponible considerando lo que ya tiene en el carrito.
            if(($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0)) > 0){
                $_SESSION['carrito'][$id_pieza] = ($_SESSION['carrito'][$id_pieza] ?? 0) + 1;
                $_SESSION['mensaje'] = "✅ Pieza agregada al carrito.";
            } else {
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente.";
            }
        }
    }
    header("Location: dashboard-piezas.php");
    exit;
}

// --- LÓGICA: FILTROS Y BÚSQUEDA ---
$busqueda = trim($_GET['buscar'] ?? '');
$marca_id = intval($_GET['marca'] ?? 0);

// Obtener marcas para el filtro lateral.
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Construcción de consulta dinámica de productos.
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";

$condiciones = [];
$params = [];
$tipos = "";

// Filtro por texto (Nombre o Descripción)
if($busqueda !== ''){
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%"; 
    $params[] = "%$busqueda%";
    $tipos .= "ss"; 
}

// Filtro por marca seleccionada
if($marca_id > 0){
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    $tipos .= "i";
}

// Unir condiciones con AND si existen.
if(count($condiciones) > 0){
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conexion->prepare($sql);
if(count($params) > 0) $stmt->bind_param($tipos,...$params);
$stmt->execute();

// Obtener todas las piezas filtradas.
$piezas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener y limpiar mensaje flash.
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Piezas - MRMP</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="pagina-principal.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    
    <style>
        /* Ajuste para que las imágenes del carrusel llenen el espacio sin deformarse. */
        .carousel-item img {
            height: 200px;
            object-fit: cover; 
            width: 100%;
        }
        /* Controles del carrusel circulares y semi-transparentes. */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-white"> <!-- Aseguramos fondo blanco en el body -->
    <!-- Navbar (Barra de Navegación) -->
    <!-- .navbar-expand-lg: Menú completo en desktop, hamburguesa en móvil. -->
    <!-- .navbar-light .bg-white: Configuración para tema claro (fondo blanco, texto oscuro). -->
    <!-- .sticky-top: Se mantiene pegada al techo al hacer scroll. -->
    <!-- .shadow-sm: Sombra suave para separar del contenido. -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <!-- .container: Centra el contenido horizontalmente. -->
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-dark" href="pagina-principal.php"><i class="fas fa-home me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active fw-bold text-primary" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- Dropdown de Usuario -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <!-- .dropdown-menu-end: Alinea el menú a la derecha. -->
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <!-- Nuevos enlaces directos a paginas independientes -->
                                <li><a class="dropdown-item" href="mis_pedidos.php"><i class="fas fa-box-open me-2"></i>Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Lista de Deseos</a></li>
                                
                                <li><a class="dropdown-item" href="carrito.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                    <span class="badge bg-primary rounded-pill ms-1"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link text-dark" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link text-dark" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <!-- .hero-catalog: Clase personalizada para el banner. -->
    <!-- .bg-white: Cambiado a fondo blanco. -->
    <section class="hero-catalog bg-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold text-dark">Catálogo de Piezas</h1>
                    <p class="lead text-secondary">Encuentra las mejores piezas automotrices de alto desempeño</p>
                </div>
                <!-- .text-lg-end: Alineación derecha en pantallas grandes. -->
                <div class="col-lg-4 text-lg-end">
                    <!-- .d-flex .align-items-center: Flexbox para alinear icono y texto. -->
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

    <!-- Main Content -->
    <main class="container my-5">
        
        <?php if($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Sección de Filtros -->
        <div class="filter-section mb-4 p-4 bg-white rounded shadow-sm">
            <div class="row g-3">
                <div class="col-md-6">
                    <form class="buscar-form" method="get">
                        <!-- .input-group: Agrupa el input y el botón de búsqueda. -->
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar pieza..." value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary text-white" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="brand-filter">
                        <strong class="d-block mb-2 text-dark">Filtrar por marca:</strong>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach($marcas as $id=>$nombre): ?>
                                <!-- Botones de filtro: Relleno si está activo, borde si no. -->
                                <a href="?marca=<?= $id ?>" class="btn btn-sm <?= $marca_id == $id ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    <?= htmlspecialchars($nombre) ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if($marca_id > 0): ?>
                                <a href="?" class="btn btn-sm btn-link text-danger text-decoration-none"><i class="fas fa-times me-1"></i>Borrar filtro</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Productos -->
        <div class="row g-4">
            <?php if(count($piezas) === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron piezas</h4>
                    <p class="text-secondary">Intenta con otros términos de búsqueda.</p>
                    <a href="?" class="btn btn-outline-primary mt-2">Ver todas las piezas</a>
                </div>
            <?php else: ?>
                <?php foreach($piezas as $p): 
                    // Lógica imagen: Usar imagen principal, luego añadir galería si existe.
                    $imagenes = [];
                    if(!empty($p['imagen'])) $imagenes[] = $p['imagen'];
                    $res_gal = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=".$p['id']);
                    while($row_gal = $res_gal->fetch_assoc()){ $imagenes[] = $row_gal['imagen']; }
                ?>
                
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <!-- .card: Tarjeta contenedora. .h-100: Altura completa para igualar filas. -->
                    <div class="card h-100 shadow-sm border-0">
                        
                        <div class="product-image bg-light position-relative">
                            <?php if(count($imagenes) > 0): ?>
                                <!-- Carrusel de imágenes del producto -->
                                <div id="carouselPieza<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
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
                                <div class="d-flex align-items-center justify-content-center h-100 py-5">
                                    <i class="fas fa-camera fa-2x text-muted opacity-25"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Badge de Stock superpuesto -->
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?> shadow-sm">
                                    <?= $p['cantidad'] > 0 ? 'En Stock: '.intval($p['cantidad']) : 'Agotado' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                            <p class="small text-muted mb-2">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            
                            <div class="mt-auto">
                                <h4 class="text-primary fw-bold mb-3">$<?= number_format($p['precio'], 2) ?></h4>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalDesc<?= $p['id'] ?>">
                                        <i class="fas fa-eye me-1"></i>Detalles
                                    </button>
                                    
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

                <!-- Modal Detalles -->
                <div class="modal fade" id="modalDesc<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header border-bottom-0">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars($p['nombre']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <?php if(count($imagenes) > 0): ?>
                                            <img src="uploads/<?= htmlspecialchars($imagenes[0]) ?>" class="img-fluid rounded shadow-sm">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 class="text-primary fw-bold mb-3">$<?= number_format($p['precio'],2) ?></h4>
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

                <!-- Modal Lightbox (Visualizador de imagen completa) -->
                <?php if(count($imagenes) > 0): ?>
                <div class="modal fade" id="lightboxModal<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <!-- .bg-transparent: Modal transparente para enfocar solo la imagen. -->
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

    <!-- Footer -->
    <footer class="bg-white text-dark py-4 mt-5 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</p>
        </div>
    </footer>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-cerrar alertas después de 5 segundos.
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });
        });
    </script>
</body>
</html>