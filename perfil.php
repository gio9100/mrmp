<?php
// session_start(): Inicia o reanuda la sesión.
session_start();

// require_once: Incluye la conexión a la base de datos.
require_once "conexion.php";

// Verificación de seguridad: Si no hay usuario logueado, redirige al login.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_secion.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];
$mensaje = "";

// --- LÓGICA: ELIMINAR CUENTA (Se mantiene) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_cuenta'])) {
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        session_destroy();
        header("Location: pagina-principal.php?mensaje=cuenta_eliminada");
        exit();
    } else {
        $mensaje = "Error al eliminar la cuenta.";
    }
}

// --- LÓGICA: SUBIR IMAGEN DE PERFIL (Se mantiene) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen_perfil'])) {
    $img = $_FILES['imagen_perfil'];
    $permitidos = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
    
    if (in_array($ext, $permitidos)) {
        $nuevo_nombre = time() . '_' . $user_id . '.' . $ext;
        $destino = "uploads/perfiles/" . $nuevo_nombre;
        
        if (!is_dir("uploads/perfiles")) mkdir("uploads/perfiles", 0777, true);
        
        if (move_uploaded_file($img['tmp_name'], $destino)) {
            $stmt = $conexion->prepare("UPDATE usuarios SET imagen = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_nombre, $user_id);
            $stmt->execute();
            $_SESSION['usuario_imagen'] = $nuevo_nombre;
            $mensaje = "✅ Imagen actualizada correctamente.";
        }
    } else {
        $mensaje = "⚠️ Formato de imagen no válido.";
    }
}

// --- LÓGICA: ACTUALIZAR DATOS (Se mantiene) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_datos'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']); // Cambiado de 'email' a 'correo' para coincidir con BD.
    
    if (!empty($nombre) && !empty($correo)) {
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $correo, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['usuario_nombre'] = $nombre;
            $mensaje = "✅ Datos actualizados correctamente.";
            // Refrescar para ver cambios inmediatos
            header("Refresh:0"); 
            exit;
        } else {
            $mensaje = "❌ Error al actualizar datos.";
        }
    }
}

// --- CONSULTA: OBTENER DATOS DEL USUARIO ---
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

// --- CORRECCIÓN DE ERRORES DE INDICE ---
// Aseguramos que las claves existan. Si en la BD se llama 'correo', aquí lo mapeamos si es necesario.
// Si 'fecha_registro' no existe en la tabla, usamos una fecha default o texto.
$usuario_email = $usuario['correo'] ?? $usuario['email'] ?? 'Sin correo';
$usuario_fecha = $usuario['fecha_registro'] ?? $usuario['created_at'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MRMP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <link href="perfil.css" rel="stylesheet">
</head>
<body class="bg-white">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="50">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navProfile">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navProfile">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="pagina-principal.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php">Piezas</a></li>
                </ul>
                <div class="navbar-nav">
                     <span class="nav-link text-dark fw-bold">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($usuario['nombre']) ?>
                    </span>
                    <!-- Menú de usuario con enlaces a las nuevas páginas -->
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Mi Cuenta</a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item active" href="perfil.php">Mis Datos</a></li>
                                <li><a class="dropdown-item" href="mis_pedidos.php">Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php">Lista de Deseos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1">Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4 text-dark fw-bold"><i class="fas fa-user-circle me-2 text-primary"></i>Mi Perfil</h2>

        <div class="row">
            <!-- Columna Izquierda: Tarjeta de Foto y Resumen -->
            <div class="col-lg-4 mb-4">
                <div class="card border-0 shadow-sm text-center p-4 bg-white">
                    <div class="position-relative d-inline-block mx-auto mb-3">
                        <?php if (!empty($usuario['imagen'])): ?>
                            <img src="uploads/perfiles/<?= htmlspecialchars($usuario['imagen']) ?>" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/150" class="rounded-circle img-thumbnail" alt="Perfil">
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="mt-2">
                            <label for="imagen_perfil" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="fas fa-camera"></i> Cambiar Foto
                            </label>
                            <input type="file" name="imagen_perfil" id="imagen_perfil" class="d-none" onchange="this.form.submit()" accept="image/*">
                        </form>
                    </div>
                    
                    <h4 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($usuario['nombre']) ?></h4>
                    <!-- Variable corregida: $usuario_email -->
                    <p class="text-secondary small"><?= htmlspecialchars($usuario_email) ?></p>
                    
                    <?php if($usuario_fecha !== 'N/A'): ?>
                        <p class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Miembro desde: <?= date('d/m/Y', strtotime($usuario_fecha)) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Datos (Sin pestañas externas) -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm bg-white">
                    <div class="card-header bg-white border-bottom pt-4 px-4 pb-0">
                        <ul class="nav nav-tabs card-header-tabs border-bottom-0">
                            <li class="nav-item">
                                <a class="nav-link active fw-bold border-bottom-0 bg-white" href="#"><i class="fas fa-address-card me-2"></i>Mis Datos Personales</a>
                            </li>
                            <!-- Enlaces rápidos a otras secciones como "tabs" falsos si se desea, o simplemente botones -->
                        </ul>
                    </div>
                    
                    <div class="card-body p-4 bg-white">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert">
                                <?= $mensaje ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold">Nombre Completo</label>
                                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-dark fw-bold">Correo Electrónico</label>
                                <!-- name="correo" para coincidir con la lógica PHP -->
                                <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($usuario_email) ?>" required>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="actualizar_datos" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-5">
                        
                        <div class="bg-light p-3 rounded border">
                            <h5 class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Zona de Peligro</h5>
                            <p class="text-secondary small mb-3">Si eliminas tu cuenta, perderás todo tu historial de pedidos y datos. Esta acción no se puede deshacer.</p>
                            <div class="text-end">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
