<?php
// session_start(): Esta función inicia una nueva sesión o reanuda la existente.
// Sirve para mantener información del usuario (como ID o carrito) disponible entre diferentes páginas.
session_start();

// require_once: Esta sentencia incluye y evalúa el archivo especificado.
// Sirve para conectar a la base de datos. Si el archivo no se encuentra, detiene el script (fatal error).
require_once "conexion.php";

// isset(): Esta función determina si una variable está definida y no es NULL.
// Aquí sirve para detectar si el usuario hizo clic en "Cerrar Sesión" (parámetro ?logout en la URL).
if(isset($_GET['logout'])){
    // session_destroy(): Esta función destruye toda la información registrada de una sesión.
    // Sirve para cerrar la sesión del usuario efectivamente.
    session_destroy();
    
    // header(): Esta función envía un encabezado HTTP sin formato.
    // Sirve para redirigir al navegador del usuario a una página limpia (sin parámetros).
    header("Location: dashboard-piezas.php");
    
    // exit: Esta construcción del lenguaje termina la ejecución del script.
    // Sirve para asegurarse de que no se ejecute más código después de la redirección.
    exit;
}

// Lógica para inicializar el carrito de compras.
// Sirve para asegurar que $_SESSION['carrito'] exista como un array vacío si el usuario está logueado pero aún no tiene carrito.
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// --- LÓGICA: AGREGAR PRODUCTO AL CARRITO ---
// Verifica si existe el parámetro 'agregar' y si es un número válido.
// is_numeric(): Esta función encuentra si una variable es un número o un string numérico.
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    
    // Verifica si el usuario NO está logueado.
    if(!isset($_SESSION['usuario_id'])){
        // Guarda un mensaje en la sesión para mostrarlo después.
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        // intval(): Esta función obtiene el valor entero de una variable.
        // Sirve para limpiar el input del usuario y asegurar que trabajamos con un número entero seguro.
        $id_pieza = intval($_GET['agregar']);
        
        // $conexion->prepare(): Esta función prepara una sentencia SQL para su ejecución.
        // Sirve para prevenir inyecciones SQL separando la consulta de los datos.
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        
        // bind_param(): Esta función vincula variables a una sentencia preparada como parámetros.
        // "i" indica que el parámetro es un entero (integer).
        $stmt->bind_param("i", $id_pieza);
        
        // execute(): Esta función ejecuta la consulta preparada.
        $stmt->execute();
        
        // get_result(): Esta función obtiene un conjunto de resultados de una sentencia preparada.
        $res = $stmt->get_result();
        
        // num_rows: Esta propiedad obtiene el número de filas en un conjunto de resultados.
        // Sirve para verificar si encontramos el producto.
        if($res && $res->num_rows==1){
            // fetch_assoc(): Esta función obtiene una fila de resultados como un array asociativo.
            $pieza = $res->fetch_assoc();
            
            // Lógica para verificar stock disponible (Stock Real - Cantidad En Carrito).
            // Sirve para no permitir agregar más productos de los que existen.
            if(($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0)) > 0){
                // Incrementa la cantidad del producto en el array de sesión.
                $_SESSION['carrito'][$id_pieza] = ($_SESSION['carrito'][$id_pieza] ?? 0) + 1;
                $_SESSION['mensaje'] = "✅ Pieza agregada al carrito.";
            } else {
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente.";
            }
        }
    }
    // Redirección para limpiar la URL (Patrón Post-Redirect-Get).
    header("Location: dashboard-piezas.php");
    exit;
}

// --- LÓGICA: FILTROS Y BÚSQUEDA ---
// trim(): Esta función elimina espacios en blanco al inicio y al final de un string.
// Sirve para limpiar la búsqueda del usuario.
$busqueda = trim($_GET['buscar'] ?? '');

// intval(): Asegura que el ID de la marca sea un número entero.
$marca_id = intval($_GET['marca'] ?? 0);

// $conexion->query(): Realiza una consulta a la base de datos.
// Sirve para obtener la lista de marcas para el filtro.
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Construcción de consulta dinámica.
// LEFT JOIN: Esta cláusula SQL une dos tablas devolviendo todas las filas de la tabla izquierda (piezas).
// Sirve para obtener el nombre de la marca asociado a cada pieza.
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";

$condiciones = [];
$params = [];
$tipos = "";

// Filtro por texto
if($busqueda !== ''){
    // LIKE: Operador SQL para buscar un patrón específico.
    // %: Comodín que representa cero, uno o varios caracteres.
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%"; 
    $params[] = "%$busqueda%";
    // "ss": Indica que hay dos parámetros de tipo string.
    $tipos .= "ss"; 
}

// Filtro por marca
if($marca_id > 0){
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    // "i": Indica que hay un parámetro de tipo entero.
    $tipos .= "i";
}

// count(): Esta función cuenta todos los elementos de un array.
// Sirve para saber si se aplicaron filtros.
if(count($condiciones) > 0){
    // implode(): Esta función une elementos de un array con un string.
    // Sirve para construir la cláusula WHERE correctamente con "AND".
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conexion->prepare($sql);
if(count($params) > 0) $stmt->bind_param($tipos,...$params);
$stmt->execute();

// fetch_all(MYSQLI_ASSOC): Obtiene todas las filas de resultados como un array asociativo.
// Sirve para tener todos los productos listos para recorrer en el HTML.
$piezas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Gestión de mensaje flash (una sola vez).
// Null Coalescing (??): Operador que devuelve el primer operando si existe y no es NULL.
$mensaje = $_SESSION['mensaje'] ?? '';
// unset(): Esta función destruye una variable especificada.
// Sirve para borrar el mensaje de la sesión para que no aparezca de nuevo al recargar.
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- <meta charset="UTF-8">: Esta etiqueta especifica la codificación de caracteres del documento. -->
    <!-- Sirve para mostrar correctamente caracteres como tildes y ñ. -->
    <meta charset="UTF-8">
    
    <!-- <meta name="viewport">: Controla las dimensiones y escala de la ventana gráfica. -->
    <!-- content="width=device-width, initial-scale=1.0": Sirve para que el sitio sea responsivo en móviles. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Piezas - MRMP</title>
    
    <!-- Enlace a Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Enlace a Font Awesome (Iconos) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <link href="pagina-principal.css" rel="stylesheet">
    <link rel="stylesheet" href="main.css">
    
    <style>
        /* object-fit: cover; Este estilo sirve para que la imagen llene el contenedor sin deformarse, recortando si es necesario. */
        .carousel-item img {
            height: 200px;
            object-fit: cover; 
            width: 100%;
        }
        /* border-radius: 50%; Este estilo sirve para hacer elementos perfectamente circulares. */
        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            background-color: rgba(0,0,0,0.5);
            border-radius: 50%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <!-- <nav>: Etiqueta semántica para navegación. -->
    <!-- .navbar-expand-lg: Clase de Bootstrap que hace que el menú se expanda en pantallas grandes. -->
    <!-- .sticky-top: Clase que pega el elemento al tope de la ventana al hacer scroll. -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
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
                        <a class="nav-link" href="pagina-principal.php"><i class="fas fa-home me-1"></i>Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard-piezas.php"><i class="fas fa-cogs me-1"></i>Piezas</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <!-- htmlspecialchars(): Convierte caracteres especiales en entidades HTML. -->
                                <!-- Sirve para prevenir ataques XSS (Cross-Site Scripting) al mostrar datos del usuario. -->
                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <li><a class="dropdown-item" href="carrito.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                    <!-- array_sum(): Calcula la suma de los valores de un array. Sirve para contar total items. -->
                                    <span class="badge bg-primary"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="inicio_secion.php"><i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión</a>
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i>Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section class="hero-catalog">
        <div class="container">
            <!-- .row: Contenedor de filas en Bootstrap (sistema Grid). -->
            <!-- .align-items-center: Centra verticalmente las columnas dentro de la fila. -->
            <div class="row align-items-center">
                <!-- .col-lg-8: Ocupa 8 de 12 columnas en pantallas grandes. -->
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

    <!-- Main Content -->
    <main class="container my-4">
        
        <?php if($mensaje): ?>
        <!-- .alert: Clase base para alertas. .alert-dismissible: Permite cerrarla. .fade .show: Animación. -->
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Sección de Filtros -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-6">
                    <form class="buscar-form" method="get">
                        <!-- .input-group: Agrupa inputs y botones en una sola línea visual. -->
                        <div class="input-group">
                            <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda) ?>">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="brand-filter">
                        <strong class="d-block mb-2">Filtrar por marca:</strong>
                        <!-- .d-flex: Activa Flexbox. .flex-wrap: Permite que los elementos bajen de línea si no caben. -->
                        <div class="d-flex flex-wrap">
                            <?php foreach($marcas as $id=>$nombre): ?>
                                <!-- Operador Ternario (Condición ? True : False). Sirve para cambiar la clase si está activo. -->
                                <a href="?marca=<?= $id ?>" class="btn btn-sm <?= $marca_id == $id ? 'btn-primary' : 'btn-outline-primary' ?> me-2 mb-2">
                                    <?= htmlspecialchars($nombre) ?>
                                </a>
                            <?php endforeach; ?>
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
            <?php if(count($piezas) === 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No se encontraron piezas</h4>
                    <a href="?" class="btn btn-primary">Ver todas las piezas</a>
                </div>
            <?php else: ?>
                <?php foreach($piezas as $p): 
                    $imagenes = [];
                    if(!empty($p['imagen'])) $imagenes[] = $p['imagen'];
                    $res_gal = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=".$p['id']);
                    while($row_gal = $res_gal->fetch_assoc()){ $imagenes[] = $row_gal['imagen']; }
                ?>
                
                <!-- .col-xl-3: Ocupa 3/12 columnas (4 por fila) en pantallas extra grandes. -->
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="product-card">
                        
                        <div class="product-image">
                            <?php if(count($imagenes) > 0): ?>
                                <!-- .carousel .slide: Componente de Bootstrap para carruseles de imágenes. -->
                                <!-- data-bs-ride="carousel": Sirve para iniciar la animación automáticamente. -->
                                <div id="carouselPieza<?= $p['id'] ?>" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php foreach($imagenes as $index => $img): ?>
                                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                                <!-- data-bs-toggle="modal": Atributo que sirve para abrir un modal al hacer clic. -->
                                                <img src="uploads/<?= htmlspecialchars($img) ?>" class="d-block w-100" style="cursor: pointer;" 
                                                     data-bs-toggle="modal" data-bs-target="#lightboxModal<?= $p['id'] ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
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
                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                    <i class="fas fa-cog fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($p['nombre']) ?></h3>
                            <div class="product-stock mb-3">
                                <!-- .badge: Clase Bootstrap para etiquetas pequeñas. -->
                                <span class="badge bg-<?= $p['cantidad'] > 0 ? 'success' : 'danger' ?>">
                                    Stock: <?= intval($p['cantidad']) ?>
                                </span>
                            </div>
                            <div class="product-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalDesc<?= $p['id'] ?>">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </button>
                                <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <a href="?agregar=<?= intval($p['id']) ?>" class="btn btn-primary btn-sm w-100"><i class="fas fa-cart-plus me-1"></i>Agregar</a>
                                <?php else: ?>
                                    <small class="d-block text-center"><a href="inicio_secion.php">Inicia sesión</a></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Detalles -->
                <!-- .modal .fade: Contenedor del modal con efecto de desvanecimiento. -->
                <div class="modal fade" id="modalDesc<?= $p['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?= htmlspecialchars($p['nombre']) ?></h5>
                                <!-- .btn-close: Botón X estándar para cerrar modales/alertas. -->
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
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
                                    <div class="col-md-7">
                                        <!-- number_format(): Formatea un número con los miles agrupados. -->
                                        <!-- Sirve para mostrar precios legibles (ej. 1,200.00). -->
                                        <p><strong>Precio:</strong> $<?= number_format($p['precio'],2) ?></p>
                                        <!-- nl2br(): Inserta saltos de línea HTML antes de cada salto de línea en el string. -->
                                        <!-- Sirve para respetar los párrafos de la descripción. -->
                                        <p><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Lightbox -->
                <?php if(count($imagenes) > 0): ?>
                <div class="modal fade" id="lightboxModal<?= $p['id'] ?>" tabindex="-1">
                    <!-- .modal-dialog-centered: Centra el modal verticalmente en la pantalla. -->
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

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <!-- date('Y'): Obtiene el año actual. Sirve para mantener el copyright actualizado. -->
            <small>&copy; <?= date('Y') ?> Mexican Racing Motor Parts.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // DOMContentLoaded: Evento que se dispara cuando el HTML ha sido completamente cargado y parseado.
        // Sirve para asegurar que el JS no intente manipular elementos que aún no existen.
        document.addEventListener('DOMContentLoaded', function() {
            // querySelectorAll: Selecciona todos los elementos que coincidan con el selector CSS.
            document.querySelectorAll('.alert').forEach(alert => {
                // setTimeout: Ejecuta una función después de un tiempo especificado (5000ms = 5s).
                // Sirve para auto-cerrar las notificaciones.
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });
        });
    </script>
</body>
</html>