<?php
session_start();
require_once "conexion.php";

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}

if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Agregar pieza al carrito
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    if(!isset($_SESSION['usuario_id'])){
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        $id_pieza = intval($_GET['agregar']);
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i",$id_pieza);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows==1){
            $pieza = $res->fetch_assoc();
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

// Filtros y búsqueda
$busqueda = trim($_GET['buscar'] ?? '');
$marca_id = intval($_GET['marca'] ?? 0);

// Marcas
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Piezas
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";
$condiciones = [];
$params = [];
$tipos = "";

if($busqueda !== ''){
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $tipos .= "ss";
}
if($marca_id>0){
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    $tipos .= "i";
}
if(count($condiciones)>0){
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}
$sql .= " ORDER BY p.id DESC";

$stmt = $conexion->prepare($sql);
if(count($params)>0) $stmt->bind_param($tipos,...$params);
$stmt->execute();
$res = $stmt->get_result();
$piezas = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Piezas - MRMP</title>
    <meta name="description" content="Catálogo completo de piezas automotrices de alta performance">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="pagina-principal.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    
    <style>
        /* Estilos para el carrusel en las tarjetas */
        .carousel-item img {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
             <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <strong></strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagina-principal.php">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
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
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="carrito.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                    <span class="badge bg-primary"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
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
    </nav>

    <!-- Hero Section -->
    <section class="hero-catalog">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold">Catálogo de Piezas</h1>
                    <p class="lead">Encuentra las mejores piezas automotrices de alto desempeño</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex align-items-center justify-content-lg-end">
                        <i class="fas fa-cogs fa-3x me-3 opacity-75"></i>
                        <div>
                            <h4 class="mb-0"><?= count($piezas) ?></h4>
                            <small>Piezas Disponibles</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main class="container my-4">
        <?php if($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-6">
                    <form class="buscar-form" method="get">
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" 
                                   placeholder="Buscar piezas por nombre o descripción..." 
                                   value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="brand-filter">
                        <strong class="d-block mb-2">Filtrar por marca:</strong>
                        <div class="d-flex flex-wrap">
                            <?php foreach($marcas as $id=>$nombre): ?>
                                <a href="?marca=<?= $id ?>" 
                                   class="btn btn-sm <?= $marca_id == $id ? 'btn-primary' : 'btn-outline-primary' ?> me-2 mb-2">
                                    <?= htmlspecialchars($nombre) ?>
                                </a>
                            <?php endforeach; ?>
                            <?php if($marca_id > 0): ?>
                                <a href="?" class="btn btn-sm btn-outline-secondary me-2 mb-2">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Piezas -->
        <div class="row g-4">
            <?php if(count($piezas) === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron piezas</h4>
                    <p class="text-muted">Intenta con otros términos de búsqueda o filtros</p>
                    <a href="?" class="btn btn-primary">Ver todas las piezas</a>
                </div>
            <?php else: ?>
                <?php foreach($piezas as $p): 
                    // Obtener imágenes adicionales
                    $imagenes = [];
                    if(!empty($p['imagen'])) $imagenes[] = $p['imagen'];
                    
                    $res_gal = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=".$p['id']);
                    while($row_gal = $res_gal->fetch_assoc()){
                        $imagenes[] = $row_gal['imagen'];
                    }
                ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if(count($imagenes) > 0): ?>
                                <div id="carouselPieza<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                <!-- Trigger del Lightbox -->
                                                <img src="uploads/<?= htmlspecialchars($img) ?>" 
                                                     class="d-block w-100" 
                                                     alt="<?= htmlspecialchars($p['nombre']) ?>"
                                                     style="cursor: pointer;"
                                                     data-bs-toggle="modal" 
                                                     data-bs-target="#lightboxModal<?= $p['id'] ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if(count($imagenes) > 1): ?>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselPieza<?= $p['id'] ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carouselPieza<?= $p['id'] ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Siguiente</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center h-100" style="height: 200px;">
                                    <i class="fas fa-cog fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-tag me-1"></i><?= htmlspecialchars($p['marca_nombre']) ?>
                            </p>
                            <div class="product-price mb-2">$<?= number_format($p['precio'],2) ?></div>
                            <div class="product-stock mb-3">
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    <i class="fas fa-<?= $p['cantidad'] > 0 ? 'check' : 'times' ?> me-1"></i>
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            <div class="product-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 ver-desc" 
                                        data-bs-toggle="modal" data-bs-target="#modalDesc<?= $p['id'] ?>">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </button>
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="?agregar=<?= intval($p['id']) ?>" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar al Carrito
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted d-block text-center">
                                        <a href="inicio_secion.php" class="text-decoration-underline">Inicia sesión</a> para comprar
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Descripción -->
                <div class="modal fade" id="modalDesc<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= htmlspecialchars($p['nombre']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-5">
                                        <!-- Carrusel en Modal también -->
                                        <?php if(count($imagenes) > 0): ?>
                                            <div id="carouselModal<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-inner">
                                                    <?php foreach($imagenes as $index => $img): ?>
                                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                            <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100 rounded" alt="<?= htmlspecialchars($p['nombre']) ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if(count($imagenes) > 1): ?>
                                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselModal<?= $p['id'] ?>" data-bs-slide="prev">
                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                        <span class="visually-hidden">Anterior</span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselModal<?= $p['id'] ?>" data-bs-slide="next">
                                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                        <span class="visually-hidden">Siguiente</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-7">
                                        <p><strong>Marca:</strong> <?= htmlspecialchars($p['marca_nombre']) ?></p>
                                        <p><strong>Precio:</strong> $<?= number_format($p['precio'],2) ?></p>
                                        <p><strong>Stock disponible:</strong> <?= intval($p['cantidad']) ?></p>
                                        <hr>
                                        <h6>Descripción:</h6>
                                        <p><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="?agregar=<?= intval($p['id']) ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-cart-plus me-1"></i>Agregar al Carrito
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Lightbox (Imagen Grande) -->
                <?php if(count($imagenes) > 0): ?>
                <div class="modal fade" id="lightboxModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl"> <!-- modal-xl para que sea grande -->
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-0">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div id="carouselLightbox<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" style="max-height: 80vh; object-fit: contain;" alt="<?= htmlspecialchars($p['nombre']) ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if(count($imagenes) > 1): ?>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselLightbox<?= $p['id'] ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carouselLightbox<?= $p['id'] ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Siguiente</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
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
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0">Mexican Racing Motor Parts</h6>
                    <small class="text-muted">Líder en piezas automotrices de alto desempeño</small>
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
                    <small class="text-muted">&copy; <?= date('Y') ?> MRMP.</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Cerrar alertas automáticamente después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>