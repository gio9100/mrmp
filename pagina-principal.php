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
<body class="mrmp-home bg-white">
    
    <!-- Navbar (Barra de Navegación) -->
    <!-- .navbar: Clase base de Bootstrap para barras de navegación. -->
    <!-- .navbar-expand-lg: Indica que el menú se expandirá (mostrará completo) en pantallas grandes (lg). En móviles se contrae. -->
    <!-- .navbar-light: Configura el texto del menú en color oscuro para contrastar con fondos claros. -->
    <!-- .bg-white: Fondo blanco para la barra. (Tema Claro). -->
    <!-- .fixed-top: Fija la barra en la parte superior, manteniéndola visible al hacer scroll. -->
    <!-- .shadow-sm: Añade una sombra pequeña y sutil abajo para separarla del contenido. -->
    <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        
        <!-- .container: Contenedor que centra y limita el ancho del contenido. -->
        <div class="container">
            
            <!-- .navbar-brand: Clase para el logo o nombre de la marca. -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text text-dark">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- .navbar-toggler: Botón "hamburguesa" que aparece en móviles. -->
            <!-- data-bs-toggle="collapse": Activa el comportamiento de colapso. -->
            <!-- data-bs-target="#navbarNav": Indica qué div se debe mostrar/ocultar. -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- .collapse .navbar-collapse: El contenido flexible que se agrupa. -->
            <div class="collapse navbar-collapse" id="navbarNav">
                
                <!-- .navbar-nav: Lista de items de navegación. -->
                <!-- .me-auto: Margin-End Auto. Empuja el contenido restante a la derecha. -->
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- .dropdown: Envuelve el menú desplegable. -->
                        <div class="nav-item dropdown">
                            <!-- .dropdown-toggle: Añade la flechita hacia abajo. -->
                            <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <!-- .dropdown-menu: La lista que aparece al hacer clic. -->
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow"> 
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <!-- Enlaces directos a paginas separadas -->
                                <li><a class="dropdown-item" href="mis_pedidos.php"><i class="fas fa-box-open me-2"></i>Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Lista de Deseos</a></li>
                                
                                <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link text-dark" href="inicio_secion.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                        </a>
                        <a class="nav-link text-dark" href="register.php">
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
            <!-- .row: Fila del sistema de grid. -->
            <!-- .align-items-center: Centra el contenido verticalmente. -->
            <!-- .min-vh-100: Altura mínima del 100% de la ventana (pantalla completa). -->
            <div class="row align-items-center min-vh-100">
                
                <!-- .col-lg-6: Columna que ocupa el 50% del ancho en pantallas grandes. -->
                <div class="col-lg-6">
                    <h1 class="hero-title text-dark">Mexican Racing Motor Parts</h1>
                    <p class="hero-subtitle text-secondary">Líder en piezas automotrices de alto Desempeño</p>
                    <p class="hero-description text-secondary">
                        Ofrecemos los mejores componentes para competición y uso profesional. 
                        Calidad, rendimiento y confianza en cada pieza.
                    </p>
                    
                    <div class="hero-buttons">
                        <!-- .btn-lg: Botón de tamaño grande. -->
                        <a href="dashboard-piezas.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-cogs me-2"></i>Explorar Piezas
                        </a>
                        <!-- .btn-outline-dark: Botón con borde oscuro y fondo transparente. -->
                        <a href="#destacadas" class="btn btn-outline-dark btn-lg">
                            <i class="fas fa-star me-2"></i>Destacados
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="hero-image">
                        <!-- .img-fluid: Ajusta la imagen al ancho de su contenedor automáticamente. -->
                        <img src="img/performance.jpeg" alt="Performance Car" class="img-fluid rounded shadow">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Piezas Destacadas -->
    <!-- .py-5: Padding en eje Y (arriba y abajo) de tamaño 5. -->
    <section id="destacadas" class="featured-section py-5 bg-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Piezas Destacadas</h2>
                <p class="section-subtitle text-muted">Las piezas más recientes de nuestro catálogo</p>
            </div>
            
            <!-- .g-4: Gap (espacio) nivel 4 entre columnas y filas. -->
            <div class="row g-4">
                <?php foreach($piezas_destacadas as $p): ?>
                
                <div class="col-md-6 col-lg-4">
                    <!-- .card: Componente tarjeta de Bootstrap. -->
                    <div class="card h-100 shadow-sm border-0 bg-white"> 
                        <div class="product-image position-relative">
                            <?php if(!empty($p['imagen'])): ?>
                                <!-- .card-img-top: Imagen superior de la tarjeta. -->
                                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="card-img-top" style="height: 250px; object-fit: cover;">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No imagen" class="card-img-top">
                            <?php endif; ?>
                            <!-- Badge personalizado posicionado absolutamente. -->
                            <div class="position-absolute top-0 end-0 m-2 badge bg-danger">Nuevo</div>
                        </div>
                        
                        <div class="card-body">
                            <!-- .card-title: Título de la tarjeta. -->
                            <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                            <!-- .text-muted: Texto en color gris suave. -->
                            <p class="card-text text-muted mb-1">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h5 mb-0 text-primary fw-bold">$<?= number_format($p['precio'], 2) ?></span>
                                <!-- Badge dinámico según stock. -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="dashboard-piezas.php?buscar=<?= urlencode($p['nombre']) ?>" class="btn btn-outline-dark btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="dashboard-piezas.php?agregar=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted text-center">
                                        <a href="inicio_secion.php" class="text-decoration-none">Inicia sesión</a> para comprar
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="dashboard-piezas.php" class="btn btn-outline-primary btn-lg px-4 rounded-pill">
                    <i class="fas fa-list me-2"></i>Ver Todas las Piezas
                </a>
            </div>
        </div>
    </section>

    <!-- Sección Sobre Nosotros -->
    <!-- .bg-white: Cambiado a fondo blanco por solicitud del cliente. -->
    <section id="about" class="about-section py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="img/sobre.jpg" alt="Sobre MRMP" class="img-fluid rounded shadow">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title mb-3">Sobre MRMP</h2>
                    <p class="lead text-secondary">
                        Mexican Racing Motor Parts es tu socio confiable en el mundo del automovilismo.
                    </p>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Calidad garantizada</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Envíos rápidos</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i>Asesoramiento experto</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Marcas -->
    <!-- Fondo blanco limpio. Solicitud específica para esta sección. -->
    <section class="brands-section py-5 bg-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Marcas que Trabajamos</h2>
            </div>
            
            <!-- .justify-content-center: Centra el contenido horizontalmente. -->
            <div class="row justify-content-center">
                <?php 
                $marcas_demo = ['TOYOTA', 'CHEVROLET', 'AUDI', 'FORD'];
                foreach($marcas_demo as $marca): 
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <!-- Tarjeta con fondo BLANCO (antes light) y texto OSCURO. -->
                    <div class="card bg-white border h-100 shadow-sm">
                        <div class="card-body text-center d-flex align-items-center justify-content-center py-4">
                            <!-- Texto oscuro para resaltar sobre el fondo blanco. -->
                            <h5 class="card-title mb-0 text-dark fw-bold"><?= $marca ?></h5>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <!-- Fondo primario (Rojo) con texto blanco para máximo impacto. -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title mb-4">¿Listo para Mejorar tu Auto?</h2>
            <a href="dashboard-piezas.php" class="btn btn-light btn-lg px-5 rounded-pill fw-bold text-primary"> 
                <i class="fas fa-cogs me-2"></i>Ver Catálogo
            </a>
        </div>
    </section>

    <!-- Footer -->
    <!-- .bg-white: Fondo blanco para el pie de página. -->
    <footer class="bg-white text-dark py-4 pt-5 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">Mexican Racing Motor Parts</h5>
                    <p class="text-secondary small">Tu tienda de confianza para el alto rendimiento.</p>
                </div>
                <!-- .text-md-end: Alinea el texto a la derecha en pantallas medianas hacia arriba. -->
                <div class="col-md-6 text-md-end">
                    <div class="social-links mb-3">
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-secondary me-3 fs-5">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="text-secondary me-3 fs-5">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <!-- date('Y'): Año dinámico. -->
                    <p class="mb-0 text-secondary small">&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS -->
    <!-- Incluye Popper para popups y dropdowns. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>