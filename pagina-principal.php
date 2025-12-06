<?php
// session_start(): Inicia una nueva sesión o reanuda la existente.
// Sirve para que podamos acceder a variables como $_SESSION['usuario_id'] en esta página.
session_start();

// require_once: Incluye el archivo de conexión.
// Sirve para establecer comunicación con la base de datos MySQL.
require_once "conexion.php";

// isset(): Verifica si la variable $_GET['logout'] está definida.
// Sirve para detectar si el usuario quiere cerrar sesión.
if(isset($_GET['logout'])){
    // session_destroy(): Destruye todos los datos asociados a la sesión actual.
    session_destroy();
    // header(): Redirige al usuario.
    // Sirve para recargar la página limpia tras el logout.
    header("Location: pagina-principal.php");
    exit;
}

// Inicialización del carrito si el usuario está conectado.
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// --- CONSULTA 1: PIEZAS DESTACADAS ---
// SELECT p.*: Selecciona todo de la tabla piezas (alias p).
// JOIN: Une tablas relacionadas.
// ORDER BY p.id DESC: Ordena por ID descendente (lo último agregado aparece primero).
// LIMIT 6: Limita los resultados a solo 6 registros.
// Sirve para mostrar las novedades en la página principal.
$sql_destacadas = "SELECT p.*, m.nombre as marca_nombre 
                   FROM piezas p 
                   LEFT JOIN marcas m ON p.marca_id = m.id 
                   ORDER BY p.id DESC 
                   LIMIT 6";

// $conexion->query(): Ejecuta la consulta SQL directamente.
$res_destacadas = $conexion->query($sql_destacadas);

// fetch_all(MYSQLI_ASSOC): Obtiene todos los resultados como un array asociativo.
$piezas_destacadas = $res_destacadas->fetch_all(MYSQLI_ASSOC);

// --- CONSULTA 2: MARCAS ---
// Sirve para mostrar un listado rápido de marcas en el pie de página o sección de marcas.
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre LIMIT 4");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[] = $m; 
}
?>

<!DOCTYPE html>
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <!-- meta viewport: Elemento crucial para el diseño responsivo. -->
    <!-- width=device-width: Ajusta el ancho de la página al ancho de la pantalla del dispositivo. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    
    <title>Mexican Racing Motor Parts - Inicio</title>
    <!-- meta description: Descripción breve del sitio. -->
    <!-- Sirve para mejorar el SEO en buscadores. -->
    <meta name="description" content="Líder en venta de piezas automotrices de alto desempeño en México.">
    
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="main.css" rel="stylesheet">
    <link href="pagina-principal.css" rel="stylesheet">
</head>
<body class="mrmp-home">
    
    <!-- Header Fijo -->
    <!-- .fixed-top: Clase CSS de Bootstrap que fija el elemento en la parte superior de la ventana. -->
    <!-- Sirve para que el menú siempre esté visible al hacer scroll. -->
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
                <!-- .me-auto: Margin End Auto. -->
                <!-- Sirve para empujar el contenido siguiente hacia la derecha (separación automática). -->
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu"> 
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="inicio_secion.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                        </a>
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Registrarse
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <!-- .min-vh-100: Altura mínima del 100% del viewport height. -->
            <!-- Sirve para que esta sección ocupe toda la altura de la pantalla del usuario. -->
            <div class="row align-items-center min-vh-100">
                
                <div class="col-lg-6">
                    <h1 class="hero-title">Mexican Racing Motor Parts</h1>
                    <p class="hero-subtitle">Líder en piezas automotrices de alto Desempeño</p>
                    <p class="hero-description">
                        Ofrecemos los mejores componentes para competición y uso profesional. 
                        Calidad, rendimiento y confianza en cada pieza.
                    </p>
                    
                    <div class="hero-buttons">
                        <a href="dashboard-piezas.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-cogs me-2"></i>Explorar Piezas
                        </a>
                        <a href="#destacadas" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-star me-2"></i>Destacados
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-image">
                        <!-- .img-fluid: Hace que la imagen sea responsiva. -->
                        <!-- Sirve para evitar que la imagen se salga de su contenedor en pantallas pequeñas. -->
                        <img src="img/performance.jpeg" alt="Performance Car" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Piezas Destacadas -->
    <!-- .py-5: Padding Y (vertical) nivel 5. -->
    <!-- Sirve para dar un espaciado generoso arriba y abajo de la sección. -->
    <section id="destacadas" class="featured-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Piezas Destacadas</h2>
                <p class="section-subtitle">Las piezas más recientes de nuestro catálogo</p>
            </div>
            
            <!-- .g-4: Gap 4. -->
            <!-- Sirve para añadir espacio entre las columnas y filas del grid. -->
            <div class="row g-4">
                <?php foreach($piezas_destacadas as $p): ?>
                
                <div class="col-md-6 col-lg-4">
                    <div class="product-card"> 
                        <div class="product-image">
                            <?php if(!empty($p['imagen'])): ?>
                                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="img-fluid">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No imagen" class="img-fluid">
                            <?php endif; ?>
                            <div class="product-badge">Nuevo</div>
                        </div>
                        
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <p class="product-brand">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            <!-- number_format(): Formatea un número con los miles agrupados. -->
                            <div class="product-price">$<?= number_format($p['precio'], 2) ?></div>
                            
                            <div class="product-stock">
                                <!-- Operador ternario para color del badge (success=verde, danger=rojo). -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            
                            <div class="product-actions">
                                <!-- urlencode(): Codifica una cadena para ser usada en una URL. -->
                                <!-- Sirve para evitar errores si el nombre tiene espacios o caracteres especiales. -->
                                <a href="dashboard-piezas.php?buscar=<?= urlencode($p['nombre']) ?>" class="btn btn-outline-primary btn-sm ver-desc">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="dashboard-piezas.php?agregar=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted d-block mt-1">
                                        <a href="inicio_secion.php" class="text-decoration-underline">Inicia sesión</a> para comprar
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="dashboard-piezas.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-list me-2"></i>Ver Todas las Piezas
                </a>
            </div>
        </div>
    </section>

    <!-- Sección Sobre Nosotros -->
    <!-- .bg-light: Fondo claro. Sirve para diferenciar secciones visualmente. -->
    <section id="about" class="about-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="img/sobre.jpg" alt="Sobre MRMP" class="img-fluid rounded">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title">Sobre MRMP</h2>
                    <!-- .lead: Clase de Bootstrap para textos destacados. -->
                    <p class="lead">
                        Mexican Racing Motor Parts es tu socio confiable en el mundo del automovilismo.
                    </p>
                    <div class="features-list">
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Calidad garantizada</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Envíos rápidos</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Asesoramiento experto</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="brands-section py-5 bg-dark text-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Marcas que Trabajamos</h2>
            </div>
            
            <!-- .justify-content-center: Centra las columnas horizontalmente. -->
            <div class="row justify-content-center">
                <?php 
                $marcas_demo = ['TOYOTA', 'CHEVROLET', 'AUDI', 'FORD'];
                foreach($marcas_demo as $marca): 
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <!-- .shadow: Añade una sombra a la tarjeta para dar profundidad. -->
                    <div class="card bg-secondary text-white h-100 shadow">
                        <div class="card-body text-center d-flex align-items-center justify-content-center">
                            <h5 class="card-title mb-0"><?= $marca ?></h5>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title">¿Listo para Mejorar tu Auto?</h2>
            <a href="dashboard-piezas.php" class="btn btn-light btn-lg"> 
                <i class="fas fa-cogs me-2"></i>Ver Todas las Piezas
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Mexican Racing Motor Parts</h5>
                </div>
                <!-- .text-md-end: Alinear texto a la derecha solo en pantallas medianas o más grandes (md). -->
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-white me-3">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="text-white me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <!-- date('Y'): Devuelve el año actual (ej. 2024). -->
                    <p class="mt-2 mb-0">&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS: Incluye Popper.js necesario para dropdowns y tooltips. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>