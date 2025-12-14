<?php
// session_start(): Función esencial que inicia una nueva sesión o reanuda la existente.
// Permite acceder a $_SESSION para leer datos del usuario logueado o del carrito.
session_start();

// require_once: Incluye el script de conexión a la base de datos.
// 'once' evita errores si el archivo ya fue incluido anteriormente.
require_once "conexion.php";

// DETECTAR LOGOUT
// Si la URL contiene ?logout=1 (parámetro GET), cerramos la sesión.
if(isset($_GET['logout'])){
    // session_destroy(): Elimina todos los datos de la sesión en el servidor.
    session_destroy();
    // header(): Redirige al script limpio para evitar reenvíos o bloqueos.
    header("Location: pagina-principal.php");
    exit; // Detiene la ejecución.
}

// INICIALIZACIÓN DEL CARRITO
// Si el usuario está registrado ($_SESSION['usuario_id']) pero no tiene carrito, lo creamos.
// Esto evita errores de 'undefined index' más adelante.
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = []; // Array vacío para el carrito.
}

// --- CONSULTA 1: PRODUCTOS DESTACADOS ---
// Obtenemos los últimos 6 productos agregados para la sección de novedades.
// LEFT JOIN marcas: unimos para traer el nombre de la marca también.
$sql_destacadas = "SELECT p.*, m.nombre as marca_nombre 
                   FROM piezas p 
                   LEFT JOIN marcas m ON p.marca_id = m.id 
                   ORDER BY p.id DESC 
                   LIMIT 6";

// query(): Ejecutamos la consulta en la base de datos.
$res_destacadas = $conexion->query($sql_destacadas);

// fetch_all(MYSQLI_ASSOC): Obtenemos TODAS las filas de una vez como un array asociativo.
// Útil para iterar después con un foreach.
$piezas_destacadas = $res_destacadas->fetch_all(MYSQLI_ASSOC);

// --- CONSULTA 2: LISTADO DE MARCAS ---
// Obtenemos algunas marcas para mostrarlas en la sección inferior.
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre LIMIT 4");
$marcas = [];
// while: Iteramos fila por fila.
while($m = $marcas_res->fetch_assoc()){
    $marcas[] = $m; 
}
?>
<!DOCTYPE html>
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <!-- meta viewport: Crucial para el diseño responsivo en móviles.
         width=device-width: el ancho de la página sigue el ancho de la pantalla.
         initial-scale=1.0: nivel de zoom inicial. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    
    <title>Performance Zone MX - Inicio</title>
    <!-- meta description: Ayuda a los motores de búsqueda (SEO) a entender de qué trata la página. -->
    <meta name="description" content="Líder en venta de piezas automotrices de alto desempeño en México.">
    
    <!-- Google Fonts: Cargamos fuentes externas (Roboto y Poppins) para mejorar la tipografía. -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS: Framework de diseño para componentes y rejilla responsiva. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome: Librería de iconos vectoriales (ej. carrito, usuario). -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado: Estilos específicos para nuestro proyecto. -->
    <link href="main.css" rel="stylesheet">
    <link href="pagina-principal.css" rel="stylesheet">
</head>
<body class="mrmp-home bg-white">
    
    <!-- BARRA DE NAVEGACIÓN (NAVBAR) -->
    <!-- .navbar-expand-lg: Se expande en pantallas grandes (desktop).
         .fixed-top: Se mantiene pegada arriba al hacer scroll. -->
    <header class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        
        <div class="container">
            
            <!-- LOGO Y MARCA -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/nuevologo.jpeg" alt="Performance Zone MX" height="70" class="d-inline-block align-text-top">
                <span class="brand-text text-dark">Performance Zone MX</span>
            </a>
            
            <!-- BOTÓN HAMBURGUESA (Móvil) -->
            <!-- data-bs-toggle="collapse": Atributo de Bootstrap 5 para funcionalidad JS sin escribir JS. -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- MENÚ COLAPSABLE -->
            <div class="collapse navbar-collapse" id="navbarNav">
                
                <!-- Enlaces Izquierdos -->
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                </ul>
                
                <!-- Enlaces Derechos (Usuario) -->
                <div class="navbar-nav">
                    <!-- Lógica PHP en vista: Mostrar menú de usuario si está logueado, sino botones de acceso. -->
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- DROPDOWN DE USUARIO -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-dark" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow"> 
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="mis_pedidos.php"><i class="fas fa-box-open me-2"></i>Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Lista de Deseos</a></li>
                                
                                <!-- array_sum(): Calculamos el total de items sumando las cantidades del array carrito. -->
                                <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- BOTONES DE ACCESO (Visitante) -->
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

    <!-- HERO SECTION (Banner Principal) -->
    <!-- Sección visual impactante para atraer al usuario. -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                
                <!-- Texto Hero Centrado -->
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="hero-title text-white">Performance Zone MX</h1>
                    <p class="hero-subtitle text-light fs-4 mb-3">Líder en piezas automotrices de alto Desempeño</p>
                    <p class="hero-description text-light opacity-75 mb-4">
                        Ofrecemos los mejores componentes para competición y uso profesional. 
                        Calidad, rendimiento y confianza en cada pieza.
                    </p>
                    
                    <div class="hero-buttons d-flex justify-content-center gap-3">
                        <a href="dashboard-piezas.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-cogs me-2"></i>Explorar Piezas
                        </a>
                        <a href="#destacadas" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-star me-2"></i>Destacados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN DE PRODUCTOS DESTACADOS -->
    <section id="destacadas" class="featured-section py-5 bg-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Piezas Destacadas</h2>
                <p class="section-subtitle text-muted">Las piezas más recientes de nuestro catálogo</p>
            </div>
            
            <!-- Grid de Productos (Bootstrap Grid) -->
            <div class="row g-4">
                <!-- foreach: Bucle para mostrar cada producto traído de la BD. -->
                <?php foreach($piezas_destacadas as $p): ?>
                
                <div class="col-md-6 col-lg-4">
                    <!-- .card: Componente tarjeta de Bootstrap. -->
                    <div class="card h-100 shadow-sm border-0 bg-white"> 
                        <div class="product-image position-relative">
                            <!-- Validación de imagen: si existe la mostramos, sino un placeholder. -->
                            <?php if(!empty($p['imagen'])): ?>
                                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="card-img-top" style="height: 250px; object-fit: cover;">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No imagen" class="card-img-top">
                            <?php endif; ?>
                            <!-- Etiqueta "Nuevo" absoluta -->
                            <div class="position-absolute top-0 end-0 m-2 badge bg-danger">Nuevo</div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                            <p class="card-text text-muted mb-1">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="h5 mb-0 text-primary fw-bold">$<?= number_format($p['precio'], 2) ?></span>
                                <!-- badge: Etiqueta de stock condicional (Verde/Rojo). -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="dashboard-piezas.php?buscar=<?= urlencode($p['nombre']) ?>" class="btn btn-outline-dark btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                
                                <!-- Botón de compra condicionado al login. -->
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

    <!-- SECCIÓN SOBRE NOSOTROS -->
    <section id="about" class="about-section py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="img/sobre.jpg" alt="Sobre MRMP" class="img-fluid rounded shadow">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title mb-3">Sobre Performance Zone MX</h2>
                    <p class="lead text-secondary">
                        Performance Zone MX es tu socio confiable en el mundo del automovilismo.
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

    <!-- SECCIÓN DE MARCAS -->
    <section class="brands-section py-5 bg-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Marcas que Trabajamos</h2>
            </div>
            
            <div class="row justify-content-center">
                <?php 
                $marcas_demo = ['TOYOTA', 'CHEVROLET', 'AUDI', 'FORD'];
                foreach($marcas_demo as $marca): 
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card bg-white border h-100 shadow-sm">
                        <div class="card-body text-center d-flex align-items-center justify-content-center py-4">
                            <h5 class="card-title mb-0 text-dark fw-bold"><?= $marca ?></h5>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- CTA (Call to Action / Llamada a la acción) -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title mb-4">¿Listo para Mejorar tu Auto?</h2>
            <a href="dashboard-piezas.php" class="btn btn-light btn-lg px-5 rounded-pill fw-bold text-primary"> 
                <i class="fas fa-cogs me-2"></i>Ver Catálogo
            </a>
        </div>
    </section>

    <!-- FOOTER (Pie de página) -->
    <footer class="bg-white text-dark py-4 pt-5 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold">Performance Zone MX</h5>
                    <p class="text-secondary small">Tu tienda de confianza para el alto rendimiento.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="social-links mb-3">
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-secondary me-3 fs-5">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="text-secondary me-3 fs-5">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <!-- date('Y'): Función PHP que imprime el año actual automáticamente (ej. 2025). -->
                    <p class="mb-0 text-secondary small">&copy; <?= date('Y') ?> Performance Zone MX.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS: Incluye Popper para tooltips y popovers. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>