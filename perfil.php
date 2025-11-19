<?php
// 1. INICIAR SESIÓN: Activa el sistema de sesiones para recordar al usuario
session_start();

// 2. CONECTAR A BASE DE DATOS: Incluye el archivo que hace conexión con MySQL
require_once "conexion.php";

// 3. VERIFICAR USUARIO LOGUEADO: Revisa si el usuario ya inició sesión
if(!isset($_SESSION['usuario_id'])){
    // 4. REDIRIGIR AL LOGIN: Si no está logueado, lo manda a iniciar sesión
    header("Location: inicio_sesion.php");
    // 5. DETENER EJECUCIÓN: No ejecuta nada más en esta página
    exit;
}

// 6. VARIABLE PARA MENSAJES: Aquí se guardan resultados de operaciones
$mensaje = "";

// 7. VERIFICAR SI SE SUBIÓ IMAGEN: Pregunta si el usuario envió una foto
if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0){
    
    // 8. TIPOS DE IMAGEN PERMITIDOS: Define qué formatos acepta el sistema
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // 9. OBTENER TIPO DE ARCHIVO: JPEG, PNG, etc.
    $tipoArchivo = $_FILES['imagen']['type'];
    
    // 10. VALIDAR TIPO DE ARCHIVO: Revisa si es un tipo permitido
    if(!in_array($tipoArchivo, $tiposPermitidos)) {
        // 11. MENSAJE ERROR TIPO ARCHIVO: Si no es permitido, muestra error
        $mensaje = "⚠️ Solo se permiten imágenes JPEG, PNG, GIF y WebP";
    } else {
        // 12. OBTENER NOMBRE ORIGINAL: "mi_foto.jpg"
        $nombreArchivo = $_FILES['imagen']['name'];
        
        // 13. EXTRAER EXTENSIÓN: Obtiene solo "jpg" o "png"
        $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        
        // 14. CREAR NOMBRE ÚNICO: "perfil_25.jpg" (25 = ID usuario)
        $nuevoNombre = "perfil_" . $_SESSION['usuario_id'] . "." . $ext;
        
        // 15. DEFINIR RUTA DE GUARDADO: "uploads/perfil_25.jpg"
        $rutaDestino = "uploads/" . $nuevoNombre;
        
        // 16. VERIFICAR SI EXISTE CARPETA: Revisa si la carpeta uploads existe
        if(!is_dir('uploads')) {
            // 17. CREAR CARPETA: Si no existe, la crea
            mkdir('uploads', 0755, true);
        }

        // 18. VERIFICAR TAMAÑO: No más de 5MB
        if($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
            // 19. MENSAJE ERROR TAMAÑO: Imagen demasiado grande
            $mensaje = "⚠️ La imagen es demasiado grande (máximo 5MB)";
        } 
        // 20. MOVER ARCHIVO: Del temporal al destino permanente
        elseif(move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)){
            
            // 21. PREPARAR SQL ACTUALIZACIÓN: Query para guardar nombre de imagen en BD
            $stmt = $conexion->prepare("UPDATE usuarios SET imagen_perfil=? WHERE id=?");
            
            // 22. REEMPLAZAR PARÁMETROS: Cambia ? por valores reales
            $stmt->bind_param("si", $nuevoNombre, $_SESSION['usuario_id']);
            
            // 23. EJECUTAR CONSULTA: Envía el comando a la base de datos
            if($stmt->execute()) {
                // 24. MENSAJE ÉXITO: Si se guardó correctamente
                $mensaje = "✅ Imagen de perfil actualizada";
            } else {
                // 25. MENSAJE ERROR BD: Si falló la base de datos
                $mensaje = "⚠️ Error al guardar en la base de datos";
            }
            // 26. CERRAR CONSULTA: Libera memoria
            $stmt->close();
        } else {
            // 27. MENSAJE ERROR SUBIR: Si no se pudo mover el archivo
            $mensaje = "⚠️ Error al subir la imagen";
        }
    }
}

// 28. VERIFICAR ACTUALIZACIÓN TELÉFONO: Si el usuario envió formulario de teléfono
if(isset($_POST['actualizar_telefono'])){
    
    // 29. OBTENER TELÉFONO: Lo que escribió el usuario en el formulario
    $telefono = trim($_POST['telefono'] ?? '');
    
    // 30. VERIFICAR TELÉFONO VACÍO: Si está vacío, quiere eliminar teléfono
    if($telefono === '') {
        // 31. PREPARAR SQL ELIMINAR: Quita teléfono y marca como no verificado
        $stmt = $conexion->prepare("UPDATE usuarios SET telefono=NULL, verificado=0 WHERE id=?");
        // 32. REEMPLAZAR PARÁMETRO: Solo ID de usuario
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        // 33. EJECUTAR CONSULTA
        if($stmt->execute()) {
            // 34. MENSAJE ÉXITO ELIMINAR
            $mensaje = "✅ Teléfono eliminado correctamente";
        } else {
            // 35. MENSAJE ERROR ELIMINAR
            $mensaje = "⚠️ Error al eliminar el teléfono";
        }
        // 36. CERRAR CONSULTA
        $stmt->close();
    } else {
        // 37. VALIDAR FORMATO TELÉFONO: Usa expresión regular para validar
        if(preg_match('/^[\+]?[0-9\s\-\(\)]{8,20}$/', $telefono)){
            // 38. PREPARAR SQL ACTUALIZAR: Guarda teléfono y verifica cuenta
            $stmt = $conexion->prepare("UPDATE usuarios SET telefono=?, verificado=1 WHERE id=?");
            // 39. REEMPLAZAR PARÁMETROS: Teléfono (string) e ID (integer)
            $stmt->bind_param("si", $telefono, $_SESSION['usuario_id']);
            // 40. EJECUTAR CONSULTA
            if($stmt->execute()) {
                // 41. MENSAJE ÉXITO ACTUALIZAR
                $mensaje = "✅ Teléfono actualizado y cuenta verificada";
            } else {
                // 42. MENSAJE ERROR ACTUALIZAR
                $mensaje = "⚠️ Error al actualizar el teléfono";
            }
            // 43. CERRAR CONSULTA
            $stmt->close();
        } else {
            // 44. MENSAJE ERROR FORMATO: Teléfono con formato inválido
            $mensaje = "⚠️ Formato de teléfono inválido";
        }
    }
}

// 45. VERIFICAR ACTUALIZACIÓN CORREO: Si el usuario envió formulario de correo
if(isset($_POST['actualizar_correo'])){
    
    // 46. OBTENER NUEVO CORREO: Lo que escribió el usuario
    $nuevo_correo = trim($_POST['nuevo_correo'] ?? '');
    
    // 47. OBTENER CONTRASEÑA ACTUAL: Para verificar identidad
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    
    // 48. VERIFICAR CAMPOS VACÍOS: Ambos campos son obligatorios
    if($nuevo_correo === '' || $contrasena_actual === '') {
        // 49. MENSAJE ERROR CAMPOS VACÍOS
        $mensaje = "⚠️ Completa todos los campos";
    } 
    // 50. VALIDAR FORMATO CORREO: Debe ser algo@algo.com
    elseif (!filter_var($nuevo_correo, FILTER_VALIDATE_EMAIL)) {
        // 51. MENSAJE ERROR FORMATO CORREO
        $mensaje = "⚠️ El correo no es válido";
    } else {
        // 52. CONSULTAR CONTRASEÑA ACTUAL: Traer hash de contraseña de BD
        $stmt = $conexion->prepare("SELECT contrasena_hash FROM usuarios WHERE id=?");
        // 53. REEMPLAZAR PARÁMETRO: ID usuario
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        // 54. EJECUTAR CONSULTA
        $stmt->execute();
        // 55. OBTENER RESULTADO
        $resultado = $stmt->get_result();
        // 56. CONVERTIR A ARRAY
        $usuario_db = $resultado->fetch_assoc();
        // 57. CERRAR CONSULTA
        $stmt->close();

        // 58. VERIFICAR CONTRASEÑA: Compara con hash en BD
        if($usuario_db && password_verify($contrasena_actual, $usuario_db['contrasena_hash'])){
            
            // 59. VERIFICAR SI CORREO YA EXISTE: En otro usuario
            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo=? AND id != ?");
            // 60. REEMPLAZAR PARÁMETROS: Nuevo correo e ID actual
            $stmt->bind_param("si", $nuevo_correo, $_SESSION['usuario_id']);
            // 61. EJECUTAR CONSULTA
            $stmt->execute();
            // 62. OBTENER RESULTADO
            $resultado = $stmt->get_result();

            // 63. VERIFICAR SI ENCONTRÓ COINCIDENCIAS
            if($resultado->num_rows > 0){
                // 64. MENSAJE ERROR CORREO EXISTENTE
                $mensaje = "⚠️ Este correo ya está registrado por otro usuario";
            } else {
                // 65. ACTUALIZAR CORREO EN BD
                $stmt = $conexion->prepare("UPDATE usuarios SET correo=? WHERE id=?");
                // 66. REEMPLAZAR PARÁMETROS
                $stmt->bind_param("si", $nuevo_correo, $_SESSION['usuario_id']);
                // 67. EJECUTAR CONSULTA
                if($stmt->execute()) {
                    // 68. ACTUALIZAR CORREO EN SESIÓN: Para mostrarlo inmediatamente
                    $_SESSION['usuario_correo'] = $nuevo_correo;
                    // 69. MENSAJE ÉXITO ACTUALIZACIÓN
                    $mensaje = "✅ Correo electrónico actualizado correctamente";
                } else {
                    // 70. MENSAJE ERROR ACTUALIZACIÓN
                    $mensaje = "⚠️ Error al actualizar el correo";
                }
                // 71. CERRAR CONSULTA
                $stmt->close();
            }
        } else {
            // 72. MENSAJE ERROR CONTRASEÑA INCORRECTA
            $mensaje = "⚠️ Contraseña actual incorrecta";
        }
    }
}

// 73. CONSULTAR DATOS ACTUALIZADOS: Traer toda la info del usuario
$stmt = $conexion->prepare("SELECT nombre, correo, telefono, imagen_perfil, verificado, fecha_creacion FROM usuarios WHERE id=?");

// 74. VERIFICAR SI CONSULTA SE PREPARÓ BIEN
if($stmt) {
    // 75. REEMPLAZAR PARÁMETRO: ID usuario
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    // 76. EJECUTAR CONSULTA
    if($stmt->execute()) {
        // 77. OBTENER RESULTADOS
        $res = $stmt->get_result();
        // 78. CONVERTIR A ARRAY ASOCIATIVO
        $usuario = $res->fetch_assoc();
        // 79. VERIFICAR SI SE ENCONTRÓ USUARIO
        if(!$usuario){
            // 80. TERMINAR SI NO SE ENCUENTRA USUARIO
            die("⚠️ Usuario no encontrado.");
        }
    } else {
        // 81. TERMINAR SI ERROR EN CONSULTA
        die("⚠️ Error al ejecutar la consulta.");
    }
    // 82. CERRAR CONSULTA
    $stmt->close();
} else {
    // 83. TERMINAR SI ERROR PREPARAR CONSULTA
    die("⚠️ Error en la preparación de la consulta.");
}

// 84. DEFINIR RUTA IMAGEN POR DEFECTO
$ruta_imagen = "img/default-avatar.png";

// 85. VERIFICAR SI USUARIO TIENE IMAGEN PERSONALIZADA
if(!empty($usuario['imagen_perfil']) && file_exists("uploads/" . $usuario['imagen_perfil'])) {
    // 86. USAR IMAGEN PERSONALIZADA SI EXISTE
    $ruta_imagen = "uploads/" . $usuario['imagen_perfil'];
}
?>
<!DOCTYPE html>
<!-- 87. INICIAR DOCUMENTO HTML: Define que es una página web -->
<html lang="es">
<!-- 88. DEFINIR IDIOMA: Español para buscadores -->
<head>
    <!-- 89. CODIFICACIÓN CARACTERES: Para que muestre ñ y acentos -->
    <meta charset="UTF-8">
    <!-- 90. CONFIGURACIÓN RESPONSIVE: Para que se vea bien en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 91. TÍTULO DE LA PÁGINA: Lo que aparece en la pestaña del navegador -->
    <title>Mi Perfil - MRMP</title>
    <!-- 92. DESCRIPCIÓN PARA BUSCADORES -->
    <meta name="description" content="Gestiona tu perfil en Mexican Racing Motor Parts">
    
    <!-- 93. CARGAR BOOTSTRAP CSS: Framework para estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 94. CARGAR FONT AWESOME: Librería de iconos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- 95. CARGAR ESTILOS PERSONALIZADOS -->
    <link href="main.css" rel="stylesheet">
    <link href="pagina-principal.css" rel="stylesheet">
</head>
<!-- 96. CUERPO DE LA PÁGINA: Todo el contenido visible -->
<body class="mrmp-home">
    <!-- 97. BARRA DE NAVEGACIÓN: Menú superior -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <!-- 98. LOGO Y NOMBRE EMPRESA -->
            <a class="navbar-brand" href="pagina-principal.php">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- 99. BOTÓN MENÚ MÓVIL: Solo visible en pantallas pequeñas -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- 100. CONTENIDO DEL MENÚ -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- 101. ENLACE INICIO -->
                    <li class="nav-item">
                        <a class="nav-link" href="pagina-principal.php">Inicio</a>
                    </li>
                    <!-- 102. ENLACE PIEZAS -->
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-piezas.php">Piezas</a>
                    </li>
                </ul>
                
                <!-- 103. MENÚ USUARIO (lado derecho) -->
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <!-- 104. BOTÓN DESPLEGABLE CON NOMBRE USUARIO -->
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($usuario['nombre']) ?>
                        </a>
                        <!-- 105. MENÚ DESPLEGABLE -->
                        <ul class="dropdown-menu">
                            <!-- 106. ENLACE CARRITO CON CONTADOR -->
                            <li><a class="dropdown-item" href="carrito.php">
                                <i class="fas fa-shopping-cart me-2"></i>Carrito 
                                <span class="badge bg-primary"><?= array_sum($_SESSION['carrito'] ?? []) ?></span>
                            </a></li>
                            <!-- 107. SEPARADOR -->
                            <li><hr class="dropdown-divider"></li>
                            <!-- 108. ENLACE CERRAR SESIÓN -->
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- 109. CONTENIDO PRINCIPAL -->
    <main class="container mt-5 pt-5">
        <div class="row">
            <div class="col-12">
                <!-- 110. MOSTRAR MENSAJES: Éxito o error -->
                <?php if($mensaje): ?>
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <?= htmlspecialchars($mensaje) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- 111. ENCABEZADO DE PÁGINA -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <!-- 112. TÍTULO PRINCIPAL -->
                        <h1 class="display-5 fw-bold text-white">Mi Perfil</h1>
                        <!-- 113. DESCRIPCIÓN -->
                        <p class="lead text-white">Gestiona tu información personal y preferencias</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <!-- 114. TARJETA FECHA REGISTRO -->
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
            <!-- 115. COLUMNA IZQUIERDA: Imagen de perfil -->
            <div class="col-lg-4 mb-4">
                <div class="card profile-card">
                    <div class="card-body text-center p-4">
                        <!-- 116. IMAGEN DE PERFIL -->
                        <img src="<?= $ruta_imagen ?>" 
                             alt="Imagen de perfil" 
                             class="profile-image mb-3"
                             onerror="this.src='img/default-avatar.png'">
                        
                        <!-- 117. NOMBRE USUARIO -->
                        <h4 class="card-title mb-2"><?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></h4>
                        <!-- 118. CORREO USUARIO -->
                        <p class="text-muted mb-3"><?= htmlspecialchars($usuario['correo'] ?? '-') ?></p>
                        
                        <!-- 119. BADGE ESTADO VERIFICACIÓN -->
                        <span class="badge <?= $usuario['verificado'] ? 'bg-success' : 'bg-warning' ?> mb-3 px-3 py-2">
                            <i class="fas <?= $usuario['verificado'] ? 'fa-check-circle' : 'fa-clock' ?> me-1"></i>
                            <?= $usuario['verificado'] ? 'Cuenta Verificada' : 'Verificación Pendiente' ?>
                        </span>

                        <!-- 120. FORMULARIO SUBIR IMAGEN -->
                        <form method="post" enctype="multipart/form-data" class="mt-4">
                            <div class="mb-3">
                                <label class="form-label info-label">Actualizar imagen de perfil</label>
                                <!-- 121. INPUT SUBIR ARCHIVO -->
                                <input type="file" name="imagen" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <!-- 122. TEXTO AYUDA -->
                                <div class="form-text">Formatos: JPEG, PNG, GIF, WebP (Máx. 5MB)</div>
                            </div>
                            <!-- 123. BOTÓN SUBIR IMAGEN -->
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-upload me-1"></i>Subir Imagen
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 124. COLUMNA DERECHA: Información y formularios -->
            <div class="col-lg-8">
                <!-- 125. TARJETA INFORMACIÓN PERSONAL -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-circle me-2"></i>Información Personal
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- 126. NOMBRE COMPLETO -->
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Nombre completo</label>
                                <span class="info-value"><?= htmlspecialchars($usuario['nombre'] ?? '-') ?></span>
                            </div>
                            <!-- 127. CORREO ELECTRÓNICO -->
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Correo electrónico</label>
                                <span class="info-value"><?= htmlspecialchars($usuario['correo'] ?? '-') ?></span>
                            </div>
                            <!-- 128. TELÉFONO -->
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Teléfono</label>
                                <span class="info-value">
                                    <?= $usuario['telefono'] ? htmlspecialchars($usuario['telefono']) : '<span class="text-muted">No agregado</span>' ?>
                                </span>
                            </div>
                            <!-- 129. ESTADO VERIFICACIÓN -->
                            <div class="col-md-6 mb-3">
                                <label class="info-label d-block">Estado de verificación</label>
                                <span class="badge <?= $usuario['verificado'] ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $usuario['verificado'] ? 'Verificado' : 'No verificado' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 130. TARJETA ACTUALIZAR CORREO -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-envelope me-2"></i>Actualizar Correo Electrónico
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <!-- 131. CAMPO NUEVO CORREO -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label info-label">Nuevo correo electrónico</label>
                                    <input type="email" name="nuevo_correo" class="form-control" 
                                           value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" 
                                           placeholder="Ingresa tu nuevo correo" required>
                                </div>
                                <!-- 132. CAMPO CONTRASEÑA ACTUAL -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label info-label">Contraseña actual</label>
                                    <input type="password" name="contrasena_actual" class="form-control" 
                                           placeholder="Confirma con tu contraseña" required>
                                </div>
                            </div>
                            <!-- 133. BOTÓN ACTUALIZAR CORREO -->
                            <button type="submit" name="actualizar_correo" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Actualizar Correo
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 134. TARJETA GESTIONAR TELÉFONO -->
                <div class="card profile-card mb-4">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-phone me-2"></i>Gestionar Teléfono
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row align-items-end">
                                <!-- 135. CAMPO TELÉFONO -->
                                <div class="col-md-8 mb-3">
                                    <label class="form-label info-label">Número de teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" 
                                           value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" 
                                           placeholder="Ej: +52 123 456 7890" 
                                           pattern="[\+]?[0-9\s\-\(\)]{8,20}">
                                    <!-- 136. TEXTO AYUDA -->
                                    <div class="form-text text-muted">
                                        Agrega tu teléfono para verificar tu cuenta
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="d-grid gap-2">
                                        <!-- 137. BOTÓN GUARDAR TELÉFONO -->
                                        <button type="submit" name="actualizar_telefono" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>
                                            <?= $usuario['telefono'] ? 'Actualizar' : 'Agregar' ?>
                                        </button>
                                        <!-- 138. BOTÓN ELIMINAR TELÉFONO (solo si existe) -->
                                        <?php if($usuario['telefono']): ?>
                                        <button type="submit" name="actualizar_telefono" class="btn btn-outline-danger btn-sm"
                                                onclick="this.form.querySelector('input[name=\'telefono\']').value = '';">
                                            <i class="fas fa-trash me-1"></i>Eliminar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- 139. TARJETA CAMBIAR CONTRASEÑA -->
                <div class="card profile-card">
                    <div class="card-header card-header-custom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-lock me-2"></i>Cambiar Contraseña
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- 140. TEXTO INFORMATIVO -->
                        <p class="info-value mb-3">¿Necesitas cambiar tu contraseña? Haz clic en el enlace a continuación:</p>
                        <!-- 141. ENLACE CAMBIAR CONTRASEÑA -->
                        <a href="recuperar.php" class="btn btn-outline-primary">
                            <i class="fas fa-key me-1"></i>Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- 142. PIE DE PÁGINA -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <!-- 143. INFORMACIÓN EMPRESA -->
                <div class="col-md-6">
                    <h5 class="text-white">Mexican Racing Motor Parts</h5>
                    <p class="mb-0 text-white-50">Tu socio confiable en piezas automotrices de competición</p>
                </div>
                <!-- 144. REDES SOCIALES Y COPYRIGHT -->
                <div class="col-md-6 text-md-end">
                    <div class="social-links">
                        <!-- 145. ENLACE FACEBOOK -->
                        <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="text-white me-3">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <!-- 146. ENLACE INSTAGRAM -->
                        <a href="#" target="_blank" class="text-white me-3">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                    <!-- 147. COPYRIGHT -->
                    <p class="mt-2 mb-0 text-white-50">&copy; <?= date('Y') ?> Mexican Racing Motor Parts. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- 148. CARGAR JAVASCRIPT DE BOOTSTRAP -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 149. SCRIPT PARA CERRAR ALERTAS AUTOMÁTICAMENTE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 150. SELECCIONAR TODAS LAS ALERTAS
            const alerts = document.querySelectorAll('.alert');
            // 151. PARA CADA ALERTA...
            alerts.forEach(alert => {
                // 152. ESPERAR 5 SEGUNDOS Y LUEGO CERRARLA
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>