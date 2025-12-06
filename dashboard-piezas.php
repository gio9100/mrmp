<?php
// session_start: Inicia o reanuda la sesión del usuario para mantener datos entre páginas
session_start();

// require_once: Incluye el archivo de conexión a la base de datos (obligatorio)
require_once "conexion.php";

// Lógica de Logout: Si la URL tiene el parámetro ?logout
if(isset($_GET['logout'])){
    // session_destroy: Destruye toda la información de la sesión actual (cierra sesión)
    session_destroy();
    // header: Redirige al usuario nuevamente a esta página (dashboard-piezas.php) fresca
    header("Location: dashboard-piezas.php");
    // exit: Detiene la ejecución del script inmediatamente
    exit;
}

// Inicialización del Carrito: Si el usuario está logueado pero no tiene carrito, se crea uno vacío
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// --- LÓGICA: AGREGAR PRODUCTO AL CARRITO ---
// Verifica si viene 'agregar' en la URL y si es un número
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    // Verificación de seguridad: ¿Está el usuario logueado?
    if(!isset($_SESSION['usuario_id'])){
        // Si no está logueado, guarda un mensaje de error en la sesión
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        // intval: Convierte el ID recibido a un número entero seguro
        $id_pieza = intval($_GET['agregar']);
        
        // Consulta SQL Preparada: Obtener el stock actual de la pieza
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        // bind_param: Vincula el ID al parámetro de la consulta (previene inyección SQL)
        $stmt->bind_param("i",$id_pieza);
        // execute: Ejecuta la consulta
        $stmt->execute();
        // get_result: Obtiene el resultado de la consulta
        $res = $stmt->get_result();
        
        // Si se encontró la pieza (1 fila retornada)
        if($res && $res->num_rows==1){
            // fetch_assoc: Convierte fila en array asociativo
            $pieza = $res->fetch_assoc();
            
            // Verificación de Stock: (Stock Real - Cantidad ya en Carrito) > 0
            if(($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0)) > 0){
                // Incrementa la cantidad de esa pieza en el carrito de la sesión
                $_SESSION['carrito'][$id_pieza] = ($_SESSION['carrito'][$id_pieza] ?? 0) + 1;
                // Mensaje de éxito
                $_SESSION['mensaje'] = "✅ Pieza agregada al carrito.";
            } else {
                // Mensaje de error si no hay suficiente stock
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente.";
            }
        }
    }
    // Redirige para limpiar la URL y evitar reenvíos
    header("Location: dashboard-piezas.php");
    exit;
}

// --- LÓGICA: FILTROS Y BÚSQUEDA ---
// Obtiene el término de búsqueda, limpiando espacios (trim)
$busqueda = trim($_GET['buscar'] ?? '');
// Obtiene el ID de la marca seleccionada, convertido a entero
$marca_id = intval($_GET['marca'] ?? 0);

// Consulta para obtener todas las marcas y mostrar en los filtros
// ORDER BY nombre: Ordena alfabéticamente
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
// Llena el array $marcas con id => nombre
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Construcción de la consulta principal de piezas (SQL Dinámico)
// LEFT JOIN: Une piezas con marcas para obtener el nombre de la marca
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";

$condiciones = []; // Array para guardar cláusulas WHERE
$params = [];      // Array para guardar valores de parámetros
$tipos = "";       // String para guardar tipos de datos (s, i, d)

// Filtro por Texto (Nombre o Descripción)
if($busqueda !== ''){
    // Busca coincidencias parciales (%) en nombre O descripción
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%"; // Valor para nombre
    $params[] = "%$busqueda%"; // Valor para descripción
    $tipos .= "ss"; // Dos strings
}

// Filtro por Marca
if($marca_id > 0){
    // Busca coincidencia exacta en ID de marca
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    $tipos .= "i"; // Un entero
}

// Si existen condiciones, se añaden al SQL con WHERE
if(count($condiciones) > 0){
    // implode: Une las condiciones con " AND "
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}

// Ordenamiento final: Productos más recientes primero
$sql .= " ORDER BY p.id DESC";

// Prepara la consulta dinámica
$stmt = $conexion->prepare($sql);
// Si hay parámetros, se vinculan dinámicamente
if(count($params) > 0) $stmt->bind_param($tipos,...$params);
// Ejecuta la consulta
$stmt->execute();
// Obtiene todas las piezas como un array asociativo
$piezas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close(); // Cierra el statement

// Gestión del mensaje flash: Se obtiene y se elimina de la sesión
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- meta charset: Define la codificación de caracteres a UTF-8 -->
    <meta charset="UTF-8">
    <!-- viewport: Configuración para que el sitio sea responsive en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Piezas - MRMP</title>
    
    <!-- Bootstrap CSS: Carga el framework de estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome: Carga la librería de iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- CSS Personalizado -->
    <link href="pagina-principal.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    
    <style>
        /* Estilos específicos para el carrusel en esta página */
        .carousel-item img {
            height: 200px; /* Altura fija para uniformidad */
            object-fit: cover; /* Recorta la imagen para llenar el contenedor */
            width: 100%; /* Ancho total */
        }
        /* Botones de navegación del carrusel mejorados */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5); /* Fondo oscuro semitransparente */
            border-radius: 50%; /* Redondeados */
            padding: 10px; /* Relleno */
        }
    </style>
</head>
<body>
    <!-- Navbar: Barra de navegación fija arriba, color oscuro -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <!-- Container: Centra el contenido horizontalmente -->
        <div class="container">
            <!-- Brand: Logotipo de la marca -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
            </a>
            
            <!-- Navbar Toggler: Botón hamburguesa para móviles -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Collapse: Contenido que se colapsa en móviles -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Nav List: Lista de enlaces alineada a la izquierda (me-auto) -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagina-principal.php"><i class="fas fa-home me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <!-- active: Indica que esta es la página actual -->
                        <a class="nav-link active" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a>
                    </li>
                </ul>
                
                <!-- Navbar Nav Derecha: Login/Registro o Menú Usuario -->
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- Dropdown de Usuario Logueado -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="carrito.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                    <!-- Badge: Contador de items en carrito -->
                                    <span class="badge bg-primary"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Enlaces para visitantes (Login/Registro) -->
                        <a class="nav-link" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section: Cabecera visual de la página -->
    <section class="hero-catalog">
        <div class="container">
            <div class="row align-items-center">
                <!-- Columna Texto: 8/12 -->
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold">Catálogo de Piezas</h1>
                    <p class="lead">Encuentra las mejores piezas automotrices de alto desempeño</p>
                </div>
                <!-- Columna Contador: 4/12 -->
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

    <!-- Main Content: Contenido principal -->
    <main class="container my-4">
        
        <!-- Alertas: Muestra mensajes de éxito/error si existen -->
        <?php if($mensaje): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Sección de Filtros -->
        <div class="filter-section">
            <div class="row">
                <!-- Buscador de Texto -->
                <div class="col-md-6">
                    <form class="buscar-form" method="get">
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <!-- Filtro de Marcas -->
                <div class="col-md-6">
                    <div class="brand-filter">
                        <strong class="d-block mb-2">Filtrar por marca:</strong>
                        <div class="d-flex flex-wrap">
                            <!-- Loop Marcas -->
                            <?php foreach($marcas as $id=>$nombre): ?>
                                <!-- Botón marca: Relleno si está activo, Borde si no -->
                                <a href="?marca=<?= $id ?>" class="btn btn-sm <?= $marca_id == $id ? 'btn-primary' : 'btn-outline-primary' ?> me-2 mb-2">
                                    <?= htmlspecialchars($nombre) ?>
                                </a>
                            <?php endforeach; ?>
                            <!-- Botón Limpiar: Solo aparece si hay filtro activo -->
                            <?php if($marca_id > 0): ?>
                                <a href="?" class="btn btn-sm btn-outline-secondary me-2 mb-2"><i class="fas fa-times me-1"></i>Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Productos -->
        <div class="row g-4">
            <!-- Caso: No se encontraron piezas -->
            <?php if(count($piezas) === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron piezas</h4>
                    <a href="?" class="btn btn-primary">Ver todas las piezas</a>
                </div>
            <?php else: ?>
                <!-- Loop: Itera sobre cada pieza encontrada -->
                <?php foreach($piezas as $p): 
                    // Obtención de imágenes (Principal + Galería)
                    $imagenes = [];
                    if(!empty($p['imagen'])) $imagenes[] = $p['imagen'];
                    $res_gal = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=".$p['id']);
                    while($row_gal = $res_gal->fetch_assoc()){ $imagenes[] = $row_gal['imagen']; }
                ?>
                
                <!-- Card Producto: Grid responsive (1 col en móvil, 2 en tablet, 3 en laptop, 4 en PC grande) -->
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="product-card">
                        
                        <!-- Imagen/Carrusel del Producto -->
                        <div class="product-image">
                            <?php if(count($imagenes) > 0): ?>
                                <!-- Carrusel Bootstrap -->
                                <div id="carouselPieza<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <!-- Item Carrusel: Activo solo el primero -->
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                <!-- Click abre Modal Lightbox -->
                                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" style="cursor: pointer;" 
                                                     data-bs-toggle="modal" data-bs-target="#lightboxModal<?= $p['id'] ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <!-- Controles Carrusel (Solo si hay >1 imagen) -->
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
                                <!-- Placeholder si no hay imagen -->
                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                    <i class="fas fa-cog fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Contenido Texto del Producto -->
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <div class="product-stock mb-3">
                                <!-- Badge Stock: Verde si positivo, Rojo si cero -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            <!-- Botones Acción -->
                            <div class="product-actions">
                                <!-- Ver Detalles (Abre Modal) -->
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalDesc<?= $p['id'] ?>">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </button>
                                <!-- Agregar Carrito (Valida Login) -->
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="?agregar=<?= intval($p['id']) ?>" class="btn btn-primary btn-sm w-100"><i class="fas fa-cart-plus me-1"></i>Agregar</a>
                                <?php else: ?>
                                    <small class="d-block text-center"><a href="inicio_secion.php">Inicia sesión</a></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Detalles del Producto -->
                <div class="modal fade" id="modalDesc<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <!-- Encabezado Modal -->
                            <div class="modal-header">
                                <h5 class="modal-title"><?= htmlspecialchars($p['nombre']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <!-- Cuerpo Modal -->
                            <div class="modal-body">
                                <div class="row">
                                    <!-- Columna Izquierda: Galería -->
                                    <div class="col-md-5">
                                        <?php if(count($imagenes) > 0): ?>
                                            <div id="carouselModal<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-inner">
                                                    <?php foreach($imagenes as $index => $img): ?>
                                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                            <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100 rounded">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if(count($imagenes) > 1): ?>
                                                    <button class="carousel-control-prev" type="button" data-bs-target="#carouselModal<?= $p['id'] ?>" data-bs-slide="prev">
                                                        <span class="carousel-control-prev-icon"></span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button" data-bs-target="#carouselModal<?= $p['id'] ?>" data-bs-slide="next">
                                                        <span class="carousel-control-next-icon"></span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Columna Derecha: Info -->
                                    <div class="col-md-7">
                                        <p><strong>Precio:</strong> $<?= number_format($p['precio'],2) ?></p>
                                        <p><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <!-- Pie Modal -->
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Lightbox (Foto Pantalla Completa) -->
                <?php if(count($imagenes) > 0): ?>
                <div class="modal fade" id="lightboxModal<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-0"><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body p-0">
                                <img src="uploads/<?= htmlspecialchars($imagenes[0]) ?>" class="d-block w-100" style="max-height: 80vh; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer: Pie de página -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <small>&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</small>
        </div>
    </footer>

    <!-- Bootstrap Bundle JS: Scripts necesarios para funcionamiento interactivo -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script JS: Auto-cierra alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            // Selecciona todas las alertas
            document.querySelectorAll('.alert').forEach(alert => {
                // Programa cierre
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });
        });
    </script>
</body>
</html>