<?php
session_start();
require_once "conexion.php";

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: pagina-principal.php");
    exit;
}

// Inicializar carrito si el usuario está logueado
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Obtener algunas piezas destacadas para mostrar en la página principal
$sql_destacadas = "SELECT p.*, m.nombre as marca_nombre 
                   FROM piezas p 
                   LEFT JOIN marcas m ON p.marca_id = m.id 
                   ORDER BY p.id DESC 
                   LIMIT 6";
$res_destacadas = $conexion->query($sql_destacadas);
$piezas_destacadas = $res_destacadas->fetch_all(MYSQLI_ASSOC);

// Obtener marcas para el footer o secciones
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mexican Racing Motor Parts - Inicio</title>
    <meta name="description" content="Líder en venta de piezas automotrices de alta calidad para competición y uso profesional">
    <meta name="keywords" content="piezas automotrices, racing, motor, competición, repuestos">
    
    <!-- Favicon -->
    <link href="" rel="icon">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="main.css" rel="stylesheet">
    <link href="pagina-principal.css" rel="stylesheet">
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
                    <li class="nav-item">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">
                            <i class="fas fa-blog me-1"></i>Blog
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
                        <img src="img/performance.jpeg" alt="Performance Car" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="destacadas" class="featured-section py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Piezas Destacadas</h2>
                <p class="section-subtitle">Las piezas más recientes de nuestro catálogo</p>
            </div>
            
            <div class="row g-4">
                <?php foreach($piezas_destacadas as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if(!empty($p['imagen'])): ?>
                                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="img-fluid">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="Imagen no disponible" class="img-fluid">
                            <?php endif; ?>
                            <div class="product-badge">Nuevo</div>
                        </div>
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <p class="product-brand">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            <div class="product-price">$<?= number_format($p['precio'], 2) ?></div>
                            <div class="product-stock">
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            <div class="product-actions">
                                <a href="#desc-<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm ver-desc">
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

    <!-- About Section -->
    <section id="about" class="about-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="img/sobre.jpg" alt="Sobre MRMP" class="img-fluid rounded">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title">Sobre MRMP</h2>
                    <p class="lead">
                        Mexican Racing Motor Parts es tu socio confiable en el mundo del automovilismo 
                        de competición y alto rendimiento.
                    </p>
                    <p>
                        Con años de experiencia en el mercado, nos especializamos en proveer piezas 
                        de la más alta calidad para entusiastas y profesionales del motorsport.
                    </p>
                    <div class="features-list">
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Calidad garantizada en todas nuestras piezas</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Envíos rápidos y seguros a todo México</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Asesoramiento técnico especializado</span>
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
        
        <div class="row justify-content-center">
            <?php 
            $marcas = ['TOYOTA', 'CHEVROLET', 'AUDI', 'FORD'];
            
            foreach($marcas as $marca): 
            ?>
            
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
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

    <!-- CTA Section -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title">¿Listo para Mejorar tu Auto?</h2>
            <p class="cta-subtitle mb-4">
                Explora nuestro catálogo completo de piezas de alta performance
            </p>
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
                    <p class="mb-0">tu aliado confiable en piezas automotrices de mayor desempeño</p>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>