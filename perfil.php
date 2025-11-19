<?php
// INICIAMOS LA SESIÓN PARA PODER USAR LOS DATOS DEL USUARIO GUARDADOS
session_start();

// INCLUIMOS EL ARCHIVO QUE NOS CONECTA A LA BASE DE DATOS
require_once "conexion.php";

// VERIFICAMOS SI EL USUARIO HA INICIADO SESIÓN
// SI NO HA INICIADO SESIÓN, LO ENVIAMOS A LA PÁGINA DE LOGIN
if(!isset($_SESSION['usuario_id'])) {
    // REDIRIGIMOS AL USUARIO A LA PÁGINA DE INICIO DE SESIÓN
    header("Location: inicio_secion.php");
    // TERMINAMOS LA EJECUCIÓN DEL SCRIPT
    exit;
}

// OBTENEMOS EL ID DEL USUARIO DESDE LA SESIÓN
$usuario_id = $_SESSION['usuario_id'];

// PREPARAMOS LA CONSULTA SQL PARA OBTENER LOS DATOS DEL USUARIO
$sql_obtener_usuario = "SELECT * FROM usuarios WHERE id = ?";

// PREPARAMOS LA CONSULTA USANDO LA CONEXIÓN A LA BASE DE DATOS
$declaracion = $conexion->prepare($sql_obtener_usuario);

// VINCULAMOS EL PARÁMETRO (LA "i" SIGNIFICA QUE ES UN NÚMERO ENTERO)
$declaracion->bind_param("i", $usuario_id);

// EJECUTAMOS LA CONSULTA
$declaracion->execute();

// OBTENEMOS EL RESULTADO DE LA CONSULTA
$resultado_consulta = $declaracion->get_result();

// OBTENEMOS LOS DATOS DEL USUARIO COMO UN ARREGLO
$datos_usuario = $resultado_consulta->fetch_assoc();

// CERRAMOS LA DECLARACIÓN PARA LIBERAR RECURSOS
$declaracion->close();

// VERIFICAMOS SI REALMENTE SE ENCONTRÓ EL USUARIO EN LA BASE DE DATOS
if(!$datos_usuario) {
    // SI NO SE ENCONTRÓ EL USUARIO, DESTRUIMOS LA SESIÓN
    session_destroy();
    // Y REDIRIGIMOS AL LOGIN
    header("Location: inicio_secion.php");
    exit;
}

// =============================================================================
// PROCESAMOS LA SUBIDA DE LA FOTO DE PERFIL CUANDO EL USUARIO ENVÍA EL FORMULARIO
// =============================================================================

// VERIFICAMOS SI SE ENVIÓ UN FORMULARIO Y SI SE SUBIÓ UN ARCHIVO DE FOTO
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto_perfil'])) {
    
    // DEFINIMOS LA CARPETA DONDE GUARDAREMOS LAS FOTOS DE PERFIL
    $carpeta_destino_fotos = "uploads/perfiles/";
    
    // VERIFICAMOS SI LA CARPETA EXISTE, SI NO EXISTE LA CREAMOS
    if(!is_dir($carpeta_destino_fotos)) {
        // CREAMOS LA CARPETA CON PERMISOS DE LECTURA Y ESCRITURA
        mkdir($carpeta_destino_fotos, 0777, true);
    }
    
    // OBTENEMOS LA EXTENSIÓN DEL ARCHIVO SUBIDO (jpg, png, etc.)
    $tipo_archivo = strtolower(pathinfo($_FILES["foto_perfil"]["name"], PATHINFO_EXTENSION));
    
    // CREAMOS UN NOMBRE ÚNICO PARA EL ARCHIVO PARA EVITAR DUPLICADOS
    $nombre_archivo_final = "perfil_" . $usuario_id . "_" . time() . "." . $tipo_archivo;
    
    // DEFINIMOS LA RUTA COMPLETA DONDE SE GUARDARÁ EL ARCHIVO
    $ruta_archivo_final = $carpeta_destino_fotos . $nombre_archivo_final;
    
    // VERIFICAMOS QUE EL ARCHIVO SEA UNA IMAGEN VÁLIDA
    $es_imagen_valida = getimagesize($_FILES["foto_perfil"]["tmp_name"]);
    
    // SI ES UNA IMAGEN VÁLIDA, PROCEDEMOS CON LAS VALIDACIONES
    if($es_imagen_valida !== false) {
        // VERIFICAMOS EL TAMAÑO DEL ARCHIVO (MÁXIMO 2MB)
        if($_FILES["foto_perfil"]["size"] > 2000000) {
            $mensaje_error_foto = "❌ La imagen es demasiado grande. Máximo 2MB permitido.";
        } 
        // VERIFICAMOS QUE SEA UN FORMATO DE IMAGEN PERMITIDO
        else if($tipo_archivo != "jpg" && $tipo_archivo != "png" && $tipo_archivo != "jpeg" && $tipo_archivo != "gif") {
            $mensaje_error_foto = "❌ Solo se permiten archivos JPG, JPEG, PNG y GIF.";
        } 
        // SI PASÓ TODAS LAS VALIDACIONES, PROCEDEMOS A SUBIR LA IMAGEN
        else {
            // PRIMERO INTENTAMOS SUBIR LA NUEVA IMAGEN
            if(move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $ruta_archivo_final)) {
                // SI LA SUBIDA FUE EXITOSA, ELIMINAMOS LA FOTO ANTERIOR SI EXISTE
                if(!empty($datos_usuario['imagen_perfil']) && file_exists($carpeta_destino_fotos . $datos_usuario['imagen_perfil'])) {
                    // ELIMINAMOS LA FOTO ANTERIOR DEL SERVIDOR
                    unlink($carpeta_destino_fotos . $datos_usuario['imagen_perfil']);
                }
                
                // ACTUALIZAMOS LA BASE DE DATOS CON LA NUEVA FOTO
                $sql_actualizar_foto = "UPDATE usuarios SET imagen_perfil = ? WHERE id = ?";
                $declaracion_actualizar = $conexion->prepare($sql_actualizar_foto);
                $declaracion_actualizar->bind_param("si", $nombre_archivo_final, $usuario_id);
                
                // EJECUTAMOS LA ACTUALIZACIÓN
                if($declaracion_actualizar->execute()) {
                    $mensaje_exito_foto = "✅ Foto de perfil actualizada correctamente.";
                    // ACTUALIZAMOS LA VARIABLE LOCAL CON EL NUEVO NOMBRE DE ARCHIVO
                    $datos_usuario['imagen_perfil'] = $nombre_archivo_final;
                    // ACTUALIZAMOS TAMBIÉN EN LA SESIÓN POR SI ACASO
                    $_SESSION['imagen_perfil'] = $nombre_archivo_final;
                } else {
                    $mensaje_error_foto = "❌ Error al actualizar en la base de datos.";
                    // SI HUBO ERROR EN LA BD, ELIMINAMOS LA IMAGEN QUE ACABAMOS DE SUBIR
                    if(file_exists($ruta_archivo_final)) {
                        unlink($ruta_archivo_final);
                    }
                }
                // CERRAMOS LA DECLARACIÓN DE ACTUALIZACIÓN
                $declaracion_actualizar->close();
            } else {
                $mensaje_error_foto = "❌ Error al subir la imagen al servidor.";
            }
        }
    } else {
        $mensaje_error_foto = "❌ El archivo no es una imagen válida.";
    }
}

// =============================================================================
// PROCESAMOS LA ELIMINACIÓN DE LA FOTO DE PERFIL (SOLO CUANDO SE HACE CLICK EN ELIMINAR)
// =============================================================================

// VERIFICAMOS SI EL USUARIO QUIERE ELIMINAR SU FOTO DE PERFIL (SOLO POR GET)
if(isset($_GET['eliminar_foto']) && $_GET['eliminar_foto'] == '1') {
    $carpeta_fotos = "uploads/perfiles/";
    
    // VERIFICAMOS SI EL USUARIO TIENE FOTO Y SI EL ARCHIVO EXISTE FÍSICAMENTE
    if(!empty($datos_usuario['imagen_perfil']) && file_exists($carpeta_fotos . $datos_usuario['imagen_perfil'])) {
        // ELIMINAMOS EL ARCHIVO FÍSICO DEL SERVIDOR
        unlink($carpeta_fotos . $datos_usuario['imagen_perfil']);
        
        // ACTUALIZAMOS LA BASE DE DATOS PARA QUITAR LA REFERENCIA A LA FOTO
        $sql_eliminar_foto = "UPDATE usuarios SET imagen_perfil = NULL WHERE id = ?";
        $declaracion_eliminar = $conexion->prepare($sql_eliminar_foto);
        $declaracion_eliminar->bind_param("i", $usuario_id);
        
        if($declaracion_eliminar->execute()) {
            $mensaje_exito_foto = "✅ Foto de perfil eliminada correctamente.";
            // ACTUALIZAMOS LA VARIABLE LOCAL
            $datos_usuario['imagen_perfil'] = null;
            // ACTUALIZAMOS LA SESIÓN
            $_SESSION['imagen_perfil'] = null;
        } else {
            $mensaje_error_foto = "❌ Error al eliminar la foto de la base de datos.";
        }
        
        $declaracion_eliminar->close();
    } else {
        $mensaje_error_foto = "❌ No se encontró la foto de perfil para eliminar.";
    }
    
    // REDIRIGIMOS PARA EVITAR REENVÍOS DEL FORMULARIO
    header("Location: perfil.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Mexican Racing Motor Parts</title>
    
    <!-- INCLUIMOS BOOTSTRAP PARA LOS ESTILOS Y COMPONENTES -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- INCLUIMOS FONT AWESOME PARA LOS ÍCONOS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- INCLUIMOS NUESTROS ARCHIVOS CSS PERSONALIZADOS -->
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="pagina-principal.css">
    <link rel="stylesheet" href="perfil.css">
</head>
<body>
    <!-- ============================================================================= -->
    <!-- ENCABEZADO DE LA PÁGINA - IGUAL AL DE PAGINA-PRINCIPAL.PHP -->
    <!-- ============================================================================= -->
    <header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <!-- LOGO Y NOMBRE DE LA EMPRESA -->
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="70" class="d-inline-block align-text-top">
                <span class="brand-text">Mexican Racing Motor Parts</span>
            </a>
            
            <!-- BOTÓN PARA MENÚ EN DISPOSITIVOS MÓVILES -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- MENÚ DE NAVEGACIÓN -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- ENLACE A LA PÁGINA DE PIEZAS -->
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard-piezas.php">
                            <i class="fas fa-cogs me-1"></i>Piezas
                        </a>
                    </li>
                    <!-- ENLACE A LA PÁGINA DEL BLOG -->
                    <li class="nav-item">
                        <a class="nav-link" href="blog.php">
                            <i class="fas fa-blog me-1"></i>Blog
                        </a>
                    </li>
                </ul>
                
                <!-- MENÚ DEL LADO DERECHO (USUARIO) -->
                <div class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                        <!-- SI EL USUARIO ESTÁ LOGUEADO, MOSTRAMOS SU MENÚ -->
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- ENLACE AL PERFIL (ACTUAL) -->
                                <li><a class="dropdown-item active" href="perfil.php"><i class="fas fa-user-circle me-2"></i>Perfil</a></li>
                                <!-- ENLACE AL CARRITO CON CONTADOR -->
                                <li><a class="dropdown-item" href="carrito.php"><i class="fas fa-shopping-cart me-2"></i>Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a></li>
                                <!-- SEPARADOR -->
                                <li><hr class="dropdown-divider"></li>
                                <!-- OPCIÓN PARA CERRAR SESIÓN -->
                                <li><a class="dropdown-item" href="pagina-principal.php?logout=1"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- SI EL USUARIO NO ESTÁ LOGUEADO, MOSTRAMOS OPCIONES DE LOGIN -->
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

    <!-- ============================================================================= -->
    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA DE PERFIL -->
    <!-- ============================================================================= -->
    <div class="container contenedor-perfil">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card tarjeta-perfil">
                    <!-- ENCABEZADO DE LA TARJETA DE PERFIL -->
                    <div class="encabezado-perfil">
                        <div class="d-flex flex-column align-items-center">
                            <?php if(!empty($datos_usuario['imagen_perfil'])): ?>
                                <!-- SI EL USUARIO TIENE FOTO DE PERFIL, LA MOSTRAMOS - USANDO imagen_perfil -->
                                <img src="uploads/perfiles/<?= htmlspecialchars($datos_usuario['imagen_perfil']) ?>" 
                                     alt="Foto de perfil" class="foto-perfil">
                            <?php else: ?>
                                <!-- SI NO TIENE FOTO, MOSTRAMOS UN AVATAR POR DEFECTO -->
                                <div class="foto-perfil avatar-sin-foto d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user fa-4x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <!-- NOMBRE DEL USUARIO -->
                            <h3 class="mt-3 nombre-usuario"><?= htmlspecialchars($datos_usuario['nombre']) ?></h3>
                            <!-- FECHA DE REGISTRO DEL USUARIO -->
                            <p class="mb-0 texto-miembro-desde">Miembro desde: <?= date('d/m/Y', strtotime($datos_usuario['fecha_creacion'])) ?></p>
                        </div>
                    </div>

                    <!-- INFORMACIÓN DEL PERFIL -->
                    <div class="informacion-perfil">
                        <!-- ============================================================================= -->
                        <!-- MOSTRAMOS MENSAJES DE ÉXITO O ERROR -->
                        <!-- ============================================================================= -->
                        
                        <?php if(isset($mensaje_exito_foto)): ?>
                            <!-- MENSAJE DE ÉXITO CUANDO LA FOTO SE ACTUALIZA CORRECTAMENTE -->
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $mensaje_exito_foto ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($mensaje_error_foto)): ?>
                            <!-- MENSAJE DE ERROR CUANDO HAY PROBLEMAS CON LA FOTO -->
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= $mensaje_error_foto ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- ============================================================================= -->
                        <!-- FORMULARIO PARA SUBIR FOTO DE PERFIL -->
                        <!-- ============================================================================= -->
                        <div class="item-informacion">
                            <div class="etiqueta-informacion">Foto de Perfil</div>
                            <!-- FORMULARIO CON ENCTYPE PARA PODER SUBIR ARCHIVOS -->
                            <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-3">
                                <div class="flex-grow-1">
                                    <!-- INPUT PARA SELECCIONAR ARCHIVO -->
                                    <input type="file" class="form-control" name="foto_perfil" accept="image/*" required>
                                    <!-- TEXTO DE AYUDA SOBRE LOS FORMATOS PERMITIDOS -->
                                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 2MB</div>
                                </div>
                                <!-- BOTÓN PARA SUBIR LA FOTO -->
                                <button type="submit" class="btn btn-subir-foto">
                                    <i class="fas fa-upload me-2"></i>Subir Foto
                                </button>
                            </form>
                            
                            <?php if(!empty($datos_usuario['imagen_perfil'])): ?>
                                <!-- SI EL USUARIO TIENE FOTO, MOSTRAMOS OPCIÓN PARA ELIMINARLA - USANDO imagen_perfil -->
                                <div class="acciones-foto">
                                    <a href="perfil.php?eliminar_foto=1" class="btn btn-outline-danger btn-sm" 
                                       onclick="return confirm('¿Estás seguro de que quieres eliminar tu foto de perfil?')">
                                        <i class="fas fa-trash me-1"></i>Eliminar Foto Actual
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ============================================================================= -->
                        <!-- INFORMACIÓN PERSONAL DEL USUARIO -->
                        <!-- ============================================================================= -->
                        
                        <!-- NOMBRE COMPLETO -->
                        <div class="item-informacion">
                            <div class="etiqueta-informacion">Nombre Completo</div>
                            <div class="valor-informacion"><?= htmlspecialchars($datos_usuario['nombre']) ?></div>
                        </div>

                        <!-- CORREO ELECTRÓNICO -->
                        <div class="item-informacion">
                            <div class="etiqueta-informacion">Correo Electrónico</div>
                            <div class="valor-informacion"><?= htmlspecialchars($datos_usuario['correo']) ?></div>
                        </div>

                        <!-- FECHA DE REGISTRO -->
                        <div class="item-informacion">
                            <div class="etiqueta-informacion">Fecha de Registro</div>
                            <div class="valor-informacion"><?= date('d/m/Y H:i', strtotime($datos_usuario['fecha_creacion'])) ?></div>
                        </div>

                        <!-- ============================================================================= -->
                        <!-- BOTONES DE ACCIÓN -->
                        <!-- ============================================================================= -->
                        <div class="d-flex gap-2 mt-4">
                            <!-- BOTÓN PARA VOLVER AL INICIO -->
                            <a href="pagina-principal.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-2"></i>Volver al Inicio
                            </a>
                            <!-- BOTÓN PARA VER LAS PIEZAS -->
                            <a href="dashboard-piezas.php" class="btn btn-primary">
                                <i class="fas fa-cogs me-2"></i>Ver Piezas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================================= -->
    <!-- PIE DE PÁGINA -->
    <!-- ============================================================================= -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <!-- INFORMACIÓN DE LA EMPRESA -->
                <div class="col-md-6">
                    <h5>Mexican Racing Motor Parts</h5>
                    <p class="mb-0">Tu aliado confiable en piezas automotrices de mayor desempeño</p>
                </div>
                <!-- REDES SOCIALES Y COPYRIGHT -->
                <div class="col-md-6 text-md-end">
                    <div class="enlaces-sociales">
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

    <!-- ============================================================================= -->
    <!-- SCRIPTS JAVASCRIPT -->
    <!-- ============================================================================= -->
    
    <!-- INCLUIMOS BOOTSTRAP JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SCRIPT PERSONALIZADO PARA FUNCIONALIDADES EXTRA -->
    <script>
        // ESTE SCRIPT SE EJECUTA CUANDO LA PÁGINA TERMINA DE CARGAR
        document.addEventListener('DOMContentLoaded', function() {
            // OBTENEMOS EL INPUT DE SUBIR FOTO
            const inputFoto = document.querySelector('input[name="foto_perfil"]');
            
            // AGREGAMOS UN EVENTO PARA CUANDO EL USUARIO SELECCIONE UNA IMAGEN
            if(inputFoto) {
                inputFoto.addEventListener('change', function(evento) {
                    // OBTENEMOS EL ARCHIVO SELECCIONADO
                    const archivo = evento.target.files[0];
                    
                    // SI SE SELECCIONÓ UN ARCHIVO, MOSTRAMOS INFORMACIÓN EN CONSOLA
                    if (archivo) {
                        console.log('✅ Imagen seleccionada:', archivo.name);
                        console.log('📏 Tamaño:', Math.round(archivo.size / 1024) + ' KB');
                        console.log('📄 Tipo:', archivo.type);
                    }
                });
            }
        });
    </script>
</body>
</html>