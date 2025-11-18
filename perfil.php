<?php
session_start();
require_once "conexion.php";

if(!isset($_SESSION['usuario_id'])){
    header("Location: inicio_sesion.php");
    exit;
}

$mensaje = "";

// Subir imagen de perfil
if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0){
    $nombreArchivo = $_FILES['imagen']['name'];
    $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    $nuevoNombre = "perfil_" . $_SESSION['usuario_id'] . "." . $ext;
    $rutaDestino = "uploads/" . $nuevoNombre;

    if(move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)){
        $stmt = $conexion->prepare("UPDATE usuarios SET imagen_perfil=? WHERE id=?");
        $stmt->bind_param("si", $nuevoNombre, $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = "✅ Imagen de perfil actualizada";
    } else {
        $mensaje = "⚠️ Error al subir la imagen";
    }
}

// Actualizar teléfono
if(isset($_POST['actualizar_telefono'])){
    $telefono = trim($_POST['telefono'] ?? '');
    if($telefono === '') {
        $stmt = $conexion->prepare("UPDATE usuarios SET telefono=NULL, verificado=0 WHERE id=?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->close();
        $mensaje = "✅ Teléfono eliminado correctamente";
    } else {
        if(preg_match('/^[0-9\s\-\+\(\)]{8,20}$/', $telefono)){
            $stmt = $conexion->prepare("UPDATE usuarios SET telefono=?, verificado=1 WHERE id=?");
            $stmt->bind_param("si", $telefono, $_SESSION['usuario_id']);
            $stmt->execute();
            $stmt->close();
            $mensaje = "✅ Teléfono actualizado y cuenta verificada";
        } else {
            $mensaje = "⚠️ Formato de teléfono inválido";
        }
    }
}

// Actualizar correo electrónico
if(isset($_POST['actualizar_correo'])){
    $nuevo_correo = trim($_POST['nuevo_correo'] ?? '');
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    if($nuevo_correo === '' || $contrasena_actual === '') {
        $mensaje = "⚠️ Completa todos los campos";
    } elseif (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no es válido";
    } else {
        $stmt = $conexion->prepare("SELECT contrasena_hash FROM usuarios WHERE id=?");
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $usuario_db = $resultado->fetch_assoc();
        $stmt->close();

        if($usuario_db && password_verify($contrasena_actual, $usuario_db['contrasena_hash'])){
            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo=? AND id != ?");
            $stmt->bind_param("si", $nuevo_correo, $_SESSION['usuario_id']);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if($resultado->num_rows > 0){
                $mensaje = "⚠️ Este correo ya está registrado por otro usuario";
            } else {
                $stmt = $conexion->prepare("UPDATE usuarios SET correo=? WHERE id=?");
                $stmt->bind_param("si", $nuevo_correo, $_SESSION['usuario_id']);
                $stmt->execute();
                $stmt->close();
                $_SESSION['usuario_correo'] = $nuevo_correo;
                $mensaje = "✅ Correo electrónico actualizado correctamente";
            }
        } else {
            $mensaje = "⚠️ Contraseña actual incorrecta";
        }
    }
}

// Obtener info del usuario actualizada
$stmt = $conexion->prepare("SELECT nombre, correo, telefono, imagen_perfil, verificado, fecha_creacion FROM usuarios WHERE id=?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
$stmt->close();

if(!$usuario){
    die("⚠️ Usuario no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - MRMP</title>
    <meta name="description" content="Gestiona tu perfil en Mexican Racing Motor Parts">
    
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/icon-loho.ico" alt="MRMP" height="40" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="pagina-principal.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-piezas.php">Piezas</a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($usuario['nombre']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="carrito.php">
                                <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                <span class="badge bg-primary"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <!-- Alert Messages -->
                <?php if($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h1 class="display-5 fw-bold text-white">Mi Perfil</h1>
                        <p class="lead text-white">Gestiona tu información personal y preferencias</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="card bg-primary text-white">
                            <div class="card-body py-3 text-center">
                                <small class="d-block">Miembro desde</small>
                                <strong class="fs-6"><?= htmlspecialchars($usuario['fecha_creacion'] ?? '-') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Profile Image Section -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card">
                    <div class="card-body text-center p-4">
                        <img src="uploads/<?= htmlspecialchars($usuario['imagen_perfil'] ?? 'default.png') ?>" 
                             alt="Imagen de perfil" 
                             class="profile-image mb-3"
                             onerror="this.src='img/default-avatar.png'">
                        
                        <h4 class="card-title text-dark-custom mb-2"><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></h4>
                        <p class="text-muted-custom mb-3"><?= htmlspecialchars($usuario['correo'] ?? '-') ?></p>
                        
                        <span class="badge <?= $usuario['verificado'] ? 'bg-success' : 'bg-warning' ?> mb-3 px-3 py-2">
                            <i class="fas <?= $usuario['verificado'] ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i>
                            <?= $usuario['verificado'] ? 'Cuenta Verificada' : 'Verificación Pendiente' ?>
                        </span>

                        <!-- Image Upload Form -->
                        <form method="post" enctype="multipart/form-data" class="mt-4">
                            <div class="mb-3">
                                <label class="form-label info-label">Actualizar imagen de perfil</label>
                                <input type="file" name="imagen" class="form-control" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-upload me-1"></i>Subir Imagen
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Profile Information Section -->
            <div class="col-lg-8">
                <!-- Personal Information Card -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle me-2"></i>Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Nombre completo</label>
                                <span class="info-value"><?= htmlspecialchars($usuario['nombre'] ?? '-') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Correo electrónico</label>
                                <span class="info-value"><?= htmlspecialchars($usuario['correo'] ?? '-') ?></span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Teléfono</label>
                                <span class="info-value">
                                    <?= $usuario['telefono'] ? htmlspecialchars($usuario['telefono']) : '<span class="text-muted-custom">No agregado</span>' ?>
                                </span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Estado de verificación</label>
                                <span class="badge <?= $usuario['verificado'] ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $usuario['verificado'] ? 'Verificado ✅' : 'No verificado ❌' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Email Card -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope me-2"></i>Actualizar Correo Electrónico
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label info-label">Nuevo correo electrónico</label>
                                    <input type="email" name="nuevo_correo" class="form-control" 
                                           value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" 
                                           placeholder="Ingresa tu nuevo correo" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label info-label">Contraseña actual</label>
                                    <input type="password" name="contrasena_actual" class="form-control" 
                                           placeholder="Confirma con tu contraseña" required>
                                </div>
                            </div>
                            <button type="submit" name="actualizar_correo" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Correo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Phone Management Card -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-phone me-2"></i>Gestionar Teléfono
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row align-items-end">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label info-label">Número de teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" 
                                           placeholder="Ej: +52 123 456 7890" 
                                           pattern="[0-9\s\-\+\(\)]{8,20}">
                                    <div class="form-text text-muted-custom">
                                        Agrega tu teléfono para verificar tu cuenta
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="actualizar_telefono" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>
                                            <?= $usuario['telefono'] ? 'Actualizar' : 'Agregar' ?>
                                        </button>
                                        <?php if($usuario['telefono']): ?>
                                        <button type="submit" name="actualizar_telefono" class="btn btn-outline-danger btn-sm"
                                                onclick="document.querySelector('input[name=\'telefono\']').value = '';">
                                            <i class="fas fa-trash me-1"></i>Eliminar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Card -->
                <div class="card profile-card">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="info-value mb-3">¿Necesitas cambiar tu contraseña? Haz clic en el enlace a continuación:</p>
                        <a href="recuperar.php" class="btn btn-outline-primary">
                            <i class="fas fa-key me-1"></i>Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-white">Mexican Racing Motor Parts</h5>
                    <p class="mb-0 text-white-50">Tu socio confiable en piezas automotrices de competición</p>
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
                    <p class="mt-2 mb-0 text-white-50">&copy; <?= date('Y') ?> Mexican Racing Motor Parts. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Auto-dismiss alerts -->
    <script>
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