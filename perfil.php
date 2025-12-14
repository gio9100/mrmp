<?php
// session_start(): Función fundamental que inicia una nueva sesión o reanuda la existente.
// Permite almacenar información del usuario (como su ID o nombre) en el servidor y acceder a ella
// a través de la superglobal $_SESSION entre diferentes páginas.
session_start();

// require_once: Instrucción que incluye y evalúa el archivo especificado ('conexion.php').
// Si el archivo ya ha sido incluido, no lo vuelve a incluir.
// Es vital para establecer la comunicación con la base de datos MySQL.
require_once "conexion.php";

// isset(): Verifica si la variable $_SESSION['usuario_id'] está definida y no es NULL.
// Esta validación asegura que solo un usuario autenticado pueda acceder a esta página.
if (!isset($_SESSION['usuario_id'])) {
    // header(): Envía un encabezado HTTP de redirección.
    // Si no hay sesión, manda al usuario de vuelta al login (inicio_secion.php).
    header("Location: inicio_secion.php");
    
    // exit(): Detiene inmediatamente la ejecución del script para asegurar que no se cargue
    // el resto de la página (riesgo de seguridad si se omite).
    exit();
}

// Asignamos el ID del usuario a una variable local para escribir menos código después.
$user_id = $_SESSION['usuario_id'];
$mensaje = ""; // Inicializamos la variable de mensajes vacía.

// --- LÓGICA: ELIMINAR CUENTA ---
// $_SERVER['REQUEST_METHOD']: Verifica si la solicitud al servidor fue tipo POST (envío de formulario).
// isset($_POST['eliminar_cuenta']): Verifica si se presionó el botón específico de eliminar cuenta.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_cuenta'])) {
    // Definimos la consulta SQL DELETE.
    // usamos '?' como marcador de posición para evitar inyección SQL.
    $sql = "DELETE FROM usuarios WHERE id = ?";
    
    // prepare(): Prepara la consulta para su ejecución segura.
    $stmt = $conexion->prepare($sql);
    
    // bind_param(): Vincula el parámetro 'i' (entero) $user_id al marcador '?'.
    $stmt->bind_param("i", $user_id);
    
    // execute(): Ejecuta la consulta preparada.
    if ($stmt->execute()) {
        // session_destroy(): Al eliminar la cuenta, debemos destruir la sesión actual para desconectar al usuario.
        session_destroy();
        
        // Redirigimos al home con un parámetro GET para mostrar un mensaje (opcional).
        header("Location: pagina-principal.php?mensaje=cuenta_eliminada");
        exit();
    } else {
        // En caso de error técnico en la BD.
        $mensaje = "Error al eliminar la cuenta.";
    }
}

// --- LÓGICA: SUBIR IMAGEN DE PERFIL ---
// $_FILES: Superglobal que contiene información sobre archivos subidos.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen_perfil'])) {
    $img = $_FILES['imagen_perfil'];
    $permitidos = ['jpg', 'jpeg', 'png', 'gif']; // Tipos de archivo permitidos.
    
    // pathinfo(): Obtiene información sobre la ruta del archivo.
    // PATHINFO_EXTENSION: Extrae solo la extensión (ej. 'jpg').
    // strtolower(): Convierte la extensión a minúsculas para comparaciones seguras.
    $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
    
    // in_array(): Verifica si la extensión ($ext) existe en el array de permitidos.
    if (in_array($ext, $permitidos)) {
        // time(): timestamp actual. Asegura que el nombre sea único y evita problemas de caché.
        $nuevo_nombre = time() . '_' . $user_id . '.' . $ext;
        
        // Ruta relativa donde se guardará el archivo en el servidor.
        $destino = "uploads/perfiles/" . $nuevo_nombre;
        
        // is_dir(): Verifica si existe el directorio. mkdir(): Lo crea si no existe (0777 permisos totales).
        if (!is_dir("uploads/perfiles")) mkdir("uploads/perfiles", 0777, true);
        
        // move_uploaded_file(): Mueve el archivo desde la carpeta temporal de PHP al destino final.
        if (move_uploaded_file($img['tmp_name'], $destino)) {
            // Actualizamos el nombre de la imagen en la base de datos.
            $stmt = $conexion->prepare("UPDATE usuarios SET imagen = ? WHERE id = ?");
            // "si": string (nombre imagen), integer (id usuario).
            $stmt->bind_param("si", $nuevo_nombre, $user_id);
            $stmt->execute();
            
            // Actualizamos la variable de sesión para que la nueva foto se vea sin tener que re-loguearse.
            $_SESSION['usuario_imagen'] = $nuevo_nombre;
            $mensaje = "✅ Imagen actualizada correctamente.";
        }
    } else {
        $mensaje = "⚠️ Formato de imagen no válido.";
    }
}

// --- LÓGICA: ACTUALIZAR DATOS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_datos'])) {
    // trim(): Elimina espacios en blanco al inicio y final del string.
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    
    // !empty(): Verifica que no estén vacíos.
    if (!empty($nombre) && !empty($correo)) {
        // UPDATE: Actualiza nombre y correo en la tabla usuarios.
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $correo, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['usuario_nombre'] = $nombre;
            $mensaje = "✅ Datos actualizados correctamente.";
            
            // header("Refresh:0"): Recarga la página actual inmediatamente para mostrar los cambios.
            header("Refresh:0"); 
            exit;
        } else {
            $mensaje = "❌ Error al actualizar datos.";
        }
    }
}

// --- CONSULTA: OBTENER DATOS DEL USUARIO ---
// Obtenemos los datos frescos de la BD para mostrarlos en el formulario.
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
// get_result()->fetch_assoc(): Obtiene el resultado y lo convierte en un array asociativo.
$usuario = $stmt->get_result()->fetch_assoc();

// Operador de fusión de null (??): Si 'correo' no existe, busca 'email'. Si ninguno, pone 'Sin correo'.
// Esto maneja posibles inconsistencias en los nombres de columnas de la BD.
$usuario_email = $usuario['correo'] ?? $usuario['email'] ?? 'Sin correo';
$usuario_fecha = $usuario['fecha_registro'] ?? $usuario['created_at'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- meta charset: Define la codificación de caracteres. UTF-8 incluye casi todos los caracteres del mundo -->
    <meta charset="UTF-8">
    <!-- meta viewport: Crucial para el diseño responsivo en móviles. Ajusta el ancho al dispositivo -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Performance Zone MX</title>
    
    <!-- EXPLICACIÓN DE RECURSOS CSS EXTERNOS -->
    <!-- Bootstrap CSS: Framework de diseño que nos da estilos predefinidos y sistema de rejilla (grid) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome: Librería de iconos vectoriales (como el usuario o la cámara) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- main.css: Nuestros estilos personalizados globales -->
    <link href="main.css" rel="stylesheet">
    <!-- perfil.css: Estilos específicos solo para esta página de perfil -->
    <link href="perfil.css" rel="stylesheet">
</head>
<!-- class="bg-white": Clase de Bootstrap que pone el fondo de la página en blanco -->
<body class="bg-white">

    <!-- nav: Elemento semántico de HTML5 para navegación -->
    <!-- navbar-expand-lg: El menú se expande en pantallas grandes (lg). navbar-light: Texto oscuro para fondos claros -->
    <!-- sticky-top: La barra se queda pegada arriba al hacer scroll. shadow-sm: Agrega una sombra suave -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <!-- container: Contenedor que centra el contenido y da márgenes -->
        <div class="container">
            <!-- navbar-brand: Clase para el logo o nombre de la marca -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/nuevologo.jpeg" alt="Performance Zone MX" height="40" class="d-inline-block align-text-top">
                Performance Zone MX
            </a>!-- navbar-toggler: Botón "hamburguesa" para móviles. data-bs-toggle="collapse": Activa el comportamiento de colapso de JS -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navProfile">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- collapse navbar-collapse: Contenido que se oculta en móviles y se muestra en el menú desplegable -->
            <div class="collapse navbar-collapse" id="navProfile">
                <!-- ms-auto: Margin Start Auto (empuja los elementos a la derecha) o me-auto para la izquierda -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="pagina-principal.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php">Piezas</a></li>
                </ul>
                <div class="navbar-nav">
                    <!-- Mostramos el nombre del usuario con un icono -->
                     <span class="nav-link text-dark fw-bold">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($usuario['nombre']) ?>
                    </span>
                    
                    <!-- Menú desplegable "Mi Cuenta" -->
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Mi Cuenta</a>
                            <!-- dropdown-menu-end: Alinea el menú a la derecha del botón -->
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item active" href="perfil.php">Mis Datos</a></li>
                                <li><a class="dropdown-item" href="mis_pedidos.php">Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php">Lista de Deseos</a></li>
                                <li><hr class="dropdown-divider"></li> <!-- Línea divisoria visual -->
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1">Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal del cuerpo. my-5: Margen vertical (eje Y) de tamaño 5 -->
    <div class="container my-5">
        <h2 class="mb-4 text-dark fw-bold"><i class="fas fa-user-circle me-2 text-primary"></i>Mi Perfil</h2>

        <!-- row: Fila del sistema de rejilla (Grid System) -->
        <div class="row">
            <!-- Columna Izquierda: Ocupa 4 columnas de 12 (1/3 del ancho) en pantallas grandes (lg) -->
            <div class="col-lg-4 mb-4">
                <!-- card: Componente visual de tarjeta. border-0: Sin borde. shadow-sm: Sombra pequeña -->
                <div class="card border-0 shadow-sm text-center p-4 bg-white">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <!-- Lógica PHP: Verifica si el usuario tiene imagen. Si no, usa un placeholder -->
                        <?php if (!empty($usuario['imagen'])): ?>
                            <!-- img-thumbnail: Estilo de foto con marco. object-fit: cover: Evita que la imagen se deforme -->
                            <img src="uploads/perfiles/<?= htmlspecialchars($usuario['imagen']) ?>" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/150" class="rounded-circle img-thumbnail" alt="Perfil">
                        <?php endif; ?>
                        
                        <!-- Formulario oculto para cambiar la foto -->
                        <!-- enctype="multipart/form-data": Obligatorio para enviar archivos por formulario -->
                        <form method="POST" enctype="multipart/form-data" class="mt-2">
                            <!-- label actúa como botón vinculado al input file oculto -->
                            <label for="imagen_perfil" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="fas fa-camera"></i> Cambiar Foto
                            </label>
                            <!-- onchange="this.form.submit()": JavaScript que envía el formulario automáticamente al seleccionar archivo -->
                            <input type="file" name="imagen_perfil" id="imagen_perfil" class="d-none" onchange="this.form.submit()" accept="image/*">
                        </form>
                    </div>
                    
                    <!-- htmlspecialchars: Función de seguridad VITAL para evitar XSS (Cross-Site Scripting) al imprimir datos del usuario -->
                    <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($usuario['nombre']) ?></h4>
                    <p class="text-secondary small"><?= htmlspecialchars($usuario_email) ?></p>
                    
                    <?php if($usuario_fecha !== 'N/A'): ?>
                        <!-- date() formatea la fecha. strtotime() convierte string de fecha a timestamp UNIX -->
                        <p class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Miembro desde: <?= date('d/m/Y', strtotime($usuario_fecha)) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha: Ocupa 8 columnas de 12 (2/3 del ancho) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-header bg-white border-bottom pt-4 px-4 pb-0">
                        <!-- nav-tabs: Pestañas de navegación visuales -->
                        <ul class="nav nav-tabs card-header-tabs border-bottom-0">
                            <li class="nav-item">
                                <a class="nav-link active fw-bold border-bottom-0 bg-white" href="#"><i class="fas fa-address-card me-2"></i>Mis Datos Personales</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4 bg-white">
                        <!-- Bloque de alerta para mensajes de éxito/error -->
                        <?php if ($mensaje): ?>
                            <!-- alert-dismissible: Permite cerrar la alerta. fade show: Animación de aparición -->
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <?= $mensaje ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario principal de edición de datos -->
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold">Nombre Completo</label>
                                <!-- value: Pre-llena el input con el valor actual de la BD -->
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold">Correo Electrónico</label>
                                <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($usuario_email) ?>" required>
                            </div>
                            <!-- d-grid gap-2: Utilidades flexbox para alinear el botón a la derecha en PC y expandirlo en móvil -->
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="actualizar_datos" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-5">
                        
                        <!-- ZONA DE PELIGRO: Sección visualmente distinta para acciones destructivas -->
                        <div class="bg-light p-3 rounded border">
                            <h5 class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Zona de Peligro</h5>
                            <p class="text-secondary small mb-3">Si eliminas tu cuenta, perderás todo tu historial de pedidos y datos. Esta acción no se puede deshacer.</p>
                            <div class="text-end">
                                <!-- onsubmit: JavaScript inline que pide confirmación antes de enviar -->
                                <form method="POST" onsubmit="return confirm('ATENCIÓN: ¿Estás seguro de que quieres eliminar tu cuenta permanentemente?');">
                                    <button type="submit" name="eliminar_cuenta" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-trash-alt me-2"></i>Eliminar mi cuenta
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS: Incluye Popper.js. Necesario para desplegables, modales y tooltips -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
