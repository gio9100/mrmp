<?php
// session_start: Inicia o reanuda la sesión existente para acceder a variables como $_SESSION['usuario_id']
session_start();

// require_once: Incluye el archivo conexion.php una sola vez; si falla, detiene el script (crítico para BD)
require_once "conexion.php";

// Lógica de Logout: Verifica si existe el parámetro 'logout' en la URL
if(isset($_GET['logout'])){
    // session_destroy: Elimina toda la información asociada a la sesión actual del servidor
    session_destroy();
    // header: Redirige al usuario a la página de inicio limpia (sin parámetros)
    header("Location: pagina-principal.php");
    // exit: Termina la ejecución inmediatamente para evitar que se procese el resto del HTML
    exit;
}

// Inicialización del Carrito: Si el usuario está logueado pero no tiene carrito, se crea un array vacío
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// --- CONSULTA 1: PIEZAS DESTACADAS ---
// SQL para obtener las 6 piezas más recientes ingresadas en la base de datos
// SELECT p.*: Selecciona todas las columnas de la tabla 'piezas' (alias p)
// m.nombre as marca_nombre: Obtiene el nombre de la marca desde la tabla 'marcas' (alias m)
// LEFT JOIN: Une las tablas coincidiendo p.marca_id con m.id
// ORDER BY p.id DESC: Ordena por ID descendente (los ID más altos son los más nuevos)
// LIMIT 6: Restringe el resultado a solo 6 registros
$sql_destacadas = "SELECT p.*, m.nombre as marca_nombre 
                   FROM piezas p 
                   LEFT JOIN marcas m ON p.marca_id = m.id 
                   ORDER BY p.id DESC 
                   LIMIT 6";

// query: Ejecuta la consulta SQL directamente (sin parámetros no necesitamos prepare)
$res_destacadas = $conexion->query($sql_destacadas);

// fetch_all: Obtiene todas las filas del resultado como un array asociativo (MYSQLI_ASSOC)
// Esto nos permite iterar sobre $piezas_destacadas fácilmente en el HTML
$piezas_destacadas = $res_destacadas->fetch_all(MYSQLI_ASSOC);

// --- CONSULTA 2: MARCAS ---
// Obtiene 4 marcas para mostrar en la sección de marcas (ejemplo estático/dinámico)
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre LIMIT 4");
$marcas = [];
// fetch_assoc: Obtiene fila por fila en un bucle while
while($m = $marcas_res->fetch_assoc()){
    $marcas[] = $m; 
}
?>

<!DOCTYPE html>
<!-- lang="es": Define el idioma del contenido para accesibilidad y SEO -->
<html lang="es"> 
<head>
    <!-- charset="UTF-8": Permite caracteres especiales como ñ y tildes -->
    <meta charset="UTF-8">
    <!-- viewport: Configuración esencial para diseño responsivo en móviles -->
    <!-- width=device-width: El ancho de la página sigue el ancho del dispositivo -->
    <!-- initial-scale=1.0: Nivel de zoom inicial al 100% -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    
    <title>Mexican Racing Motor Parts - Inicio</title>
    <!-- Meta descripción para SEO (motores de búsqueda) -->
    <meta name="description" content="Líder en venta de piezas automotrices de alto desempeño en México.">
    
    <!-- Google Fonts: Carga asíncrona de fuentes Roboto y Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS: Framework principal para grid y componentes UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6: Librería de iconos vectoriales (fa-user, fa-cogs, etc.) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Hojas de estilo personalizadas -->
    <link href="main.css" rel="stylesheet"> <!-- Estilos globales -->
    <link href="pagina-principal.css" rel="stylesheet"> <!-- Estilos específicos de esta página -->
</head>
<body class="mrmp-home">
    
    <!-- HEADER / NAVBAR -->
    <!-- navbar-expand-lg: El menú se expande (visible) en pantallas grandes (lg) -->
    <!-- navbar-dark: Ajusta el color del texto para fondos oscuros -->
    <!-- bg-dark: Fondo negro/oscuro de Bootstrap -->
    <!-- fixed-top: Fija la barra en la parte superior al hacer scroll -->
    <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container"> <!-- Centra el contenido horizontalmente -->
            
            <!-- Marca/Logo del sitio -->
            <a class="navbar-brand" href="pagina-principal.php">
                <!-- d-inline-block: Muestra la imagen en línea con el texto -->
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- Botón Hamburguesa: Visible solo en móviles para desplegar el menú -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Contenido Colapsable del Menú -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Lista Izquierda: me-auto empuja el contenido restante a la derecha -->
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                </ul>
                
                <!-- Lista Derecha: Usuario y Carrito -->
                <div class="navbar-nav">
                    <!-- Verifica si el usuario está logueado -->
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- Dropdown de Usuario -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <!-- htmlspecialchars: Previene XSS al mostrar datos de usuario -->
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu"> <!-- Menú desplegable -->
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <!-- Muestra conteo del carrito sumando valores del array -->
                                <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <li><hr class="dropdown-divider"></li> <!-- Separador visual -->
                                <li><a class="dropdown-item" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Opciones para Visitantes -->
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

    <!-- HERO SECTION: Portada visual de alto impacto -->
    <section class="hero-section">
        <div class="container">
            <!-- row: Fila Flexbox -->
            <!-- align-items-center: Centra verticalmente el contenido de las columnas -->
            <!-- min-vh-100: Altura mínima del 100% del viewport (pantalla completa vertical) -->
            <div class="row align-items-center min-vh-100">
                
                <!-- Columna Texto: Ocupa 6/12 columnas en pantallas grandes (lg) -->
                <div class="col-lg-6">
                    <h1 class="hero-title">Mexican Racing Motor Parts</h1>
                    <p class="hero-subtitle">Líder en piezas automotrices de alto Desempeño</p>
                    <p class="hero-description">
                        Ofrecemos los mejores componentes para competición y uso profesional. 
                        Calidad, rendimiento y confianza en cada pieza.
                    </p>
                    
                    <!-- Botones de Acción (Call to Action) -->
                    <div class="hero-buttons">
                        <!-- btn-primary: Color principal sólido -->
                        <a href="dashboard-piezas.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-cogs me-2"></i>Explorar Piezas
                        </a>
                        <!-- btn-outline-light: Borde blanco transparente -->
                        <a href="#destacadas" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-star me-2"></i>Destacados
                        </a>
                    </div>
                </div>
                
                <!-- Columna Imagen: Ocupa 6/12 columnas en pantallas grandes -->
                <div class="col-lg-6">
                    <div class="hero-image">
                        <!-- img-fluid: Hace que la imagen sea responsiva (max-width: 100%) -->
                        <img src="img/performance.jpeg" alt="Performance Car" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN PIEZAS DESTACADAS -->
    <!-- py-5: Padding vertical (arriba y abajo) nivel 5 -->
    <section id="destacadas" class="featured-section py-5">
        <div class="container">
            <!-- Encabezado de sección centrado -->
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Piezas Destacadas</h2>
                <p class="section-subtitle">Las piezas más recientes de nuestro catálogo</p>
            </div>
            
            <!-- Grid de Productos -->
            <!-- g-4: Gap (espacio) entre columnas y filas nivel 4 -->
            <div class="row g-4">
                <!-- Foreach: Itera sobre cada pieza destacada recuperada de la BD -->
                <?php foreach($piezas_destacadas as $p): ?>
                
                <!-- Columnas Responsivas: -->
                <!-- col-md-6: 2 columnas en Tablet -->
                <!-- col-lg-4: 3 columnas en Laptop/Desktop -->
                <div class="col-md-6 col-lg-4">
                    <div class="product-card"> <!-- Contenedor Tarjeta -->
                        <div class="product-image">
                            <!-- Helper para mostrar imagen o placeholder si no existe -->
                            <?php if(!empty($p['imagen'])): ?>
                                <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="img-fluid">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No imagen" class="img-fluid">
                            <?php endif; ?>
                            <!-- Badge 'Nuevo' absoluto sobre la imagen -->
                            <div class="product-badge">Nuevo</div>
                        </div>
                        
                        <div class="product-content">
                            <!-- Título del producto -->
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <!-- Marca del producto -->
                            <p class="product-brand">Marca: <?= htmlspecialchars($p['marca_nombre']) ?></p>
                            <!-- Precio formateado a 2 decimales -->
                            <div class="product-price">$<?= number_format($p['precio'], 2) ?></div>
                            
                            <div class="product-stock">
                                <!-- Lógica ternaria para color del badge de stock (Verde/Rojo) -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            
                            <div class="product-actions">
                                <!-- Botón Ver Detalles (Busca por nombre como ejemplo) -->
                                <a href="dashboard-piezas.php?buscar=<?= urlencode($p['nombre']) ?>" class="btn btn-outline-primary btn-sm ver-desc">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                
                                <!-- Botón Agregar al Carrito (Condicional de Sesión) -->
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="dashboard-piezas.php?agregar=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar
                                    </a>
                                <?php else: ?>
                                    <!-- Mensaje para invitar al login si no hay sesión -->
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
            
            <!-- Botón Ver Todo al final de la sección -->
            <div class="text-center mt-4">
                <a href="dashboard-piezas.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-list me-2"></i>Ver Todas las Piezas
                </a>
            </div>
        </div>
    </section>

    <!-- SECCIÓN SOBRE NOSOTROS -->
    <!-- bg-light: Fondo gris muy claro para diferenciar secciones -->
    <section id="about" class="about-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <img src="img/sobre.jpg" alt="Sobre MRMP" class="img-fluid rounded">
                </div>
                <div class="col-lg-6">
                    <h2 class="section-title">Sobre MRMP</h2>
                    <p class="lead"> <!-- .lead: Texto ligeramente más grande y fino para destacar -->
                        Mexican Racing Motor Parts es tu socio confiable en el mundo del automovilismo.
                    </p>
                    <!-- Lista de características con iconos -->
                    <div class="features-list">
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i> <!-- Icono Check Azul -->
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

    <!-- SECCIÓN MARCAS (Estática para demostración visual) -->
    <!-- bg-dark text-white: Fondo oscuro y texto blanco -->
    <section class="brands-section py-5 bg-dark text-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="section-title">Marcas que Trabajamos</h2>
            </div>
            
            <div class="row justify-content-center">
                <?php 
                // Array simple para generar tarjetas de marcas
                $marcas_demo = ['TOYOTA', 'CHEVROLET', 'AUDI', 'FORD'];
                foreach($marcas_demo as $marca): 
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <!-- Tarjeta simple gris (bg-secondary) -->
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

    <!-- CTA SECTION (Llamada a la acción final) -->
    <section class="cta-section py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="cta-title">¿Listo para Mejorar tu Auto?</h2>
            <a href="dashboard-piezas.php" class="btn btn-light btn-lg"> <!-- Botón blanco para contraste -->
                <i class="fas fa-cogs me-2"></i>Ver Todas las Piezas
            </a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Mexican Racing Motor Parts</h5>
                </div>
                <!-- Alineación derecha en desktop (text-md-end) -->
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <!-- Enlaces a redes sociales -->
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-white me-3">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="text-white me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <!-- Copyright dinámico con el año actual -->
                    <p class="mt-2 mb-0">&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS: Incluye Popper para dropdows y modales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script JS Principal -->
    <script src="assets/js/main.js"></script>
</body>
</html>