<?php
// 1: Iniciamos la sesión pa' guardar datos del usuario mientras navega
session_start();
// 2: Traemos el archivo de conexión pa' poder hablar con la base de datos
require_once "conexion.php";

// 3: Esta es la clave secreta pa' crear admins, puro VIP
$claveCreador = "CBTIS52";
// 4: Checamos si hay mensaje en la sesión, si no, lo dejamos vacío
$mensaje = $_SESSION['mensaje'] ?? '';
// 5: Borramos el mensaje de la sesión pa' que no salga a cada rato
unset($_SESSION['mensaje']);

// 6: Comentario pa' saber que aquí es el Logout
// Logout
// 7: Si en la URL viene la variable logout...
if(isset($_GET['logout'])){
    // 8: Destruimos la sesión, bye bye
    session_destroy();
    // 9: Lo regresamos al inicio del panel
    header("Location: admin_panel.php");
    // 10: Matamos el script aquí
    exit;
// 11: Cerramos el if del logout
}

// 12: Comentario: Aquí creamos un nuevo admin
// Crear nuevo admin
// 13: Si mandaron el formulario de crear admin...
if(isset($_POST['crear_admin'])){
    // 14: Limpiamos el nombre de espacios
    $nombre = trim($_POST['nombre']);
    // 15: Limpiamos el correo también
    $correo = trim($_POST['correo']);
    // 16: Agarramos la contraseña
    $contrasena = $_POST['contrasena'];
    // 17: Agarramos la clave secreta que pusieron
    $clave = $_POST['clave_creador'];

    // 18: Si la clave no coincide con la nuestra...
    if($clave !== $claveCreador){
        // 19: Mensaje de error, tache guarache
        $_SESSION['mensaje'] = "❌ Clave del creador incorrecta.";
    // 20: Si la clave está bien y llenaron todo...
    } elseif($nombre && $correo && $contrasena){
        // 21: Encriptamos la contraseña pa' que sea segura
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        // 22: Preparamos la consulta SQL pa' insertar
        $stmt = $conexion->prepare("INSERT INTO admins(nombre, correo, contrasena_hash) VALUES(?,?,?)");
        // 23: Le pasamos los datos a la consulta
        $stmt->bind_param("sss",$nombre,$correo,$hash);
        // 24: Ejecutamos la consulta, ¡pum!
        $stmt->execute();
        // 25: Cerramos el statement pa' liberar memoria
        $stmt->close();
        // 26: Mensaje de éxito, todo chido
        $_SESSION['mensaje'] = "✅ Admin creado correctamente.";
    // 27: Cerramos el elseif
    }
    // 28: Recargamos la página
    header("Location: admin_panel.php");
    // 29: Bye
    exit;
// 30: Cerramos el if de crear admin
}

// 31: Comentario: Login del admin
// Login admin
// 32: Si mandaron el form de login...
if(isset($_POST['login_admin'])){
    // 33: Limpiamos el correo
    $correo = trim($_POST['correo']);
    // 34: Agarramos la contraseña
    $contrasena = $_POST['contrasena'];

    // 35: Buscamos al admin por su correo
    $stmt = $conexion->prepare("SELECT * FROM admins WHERE correo=?");
    // 36: Pasamos el correo al query
    $stmt->bind_param("s",$correo);
    // 37: Ejecutamos la búsqueda
    $stmt->execute();
    // 38: Obtenemos el resultado
    $res = $stmt->get_result();

    // 39: Si encontramos algo y es solo uno...
    if($res && $res->num_rows==1){
        // 40: Sacamos los datos del admin
        $admin = $res->fetch_assoc();
        // 41: Verificamos si la contraseña coincide con el hash
        if(password_verify($contrasena, $admin['contrasena_hash'])){
            // 42: Guardamos el ID en la sesión
            $_SESSION['admin_id'] = $admin['id'];
            // 43: Guardamos el nombre también
            $_SESSION['admin_nombre'] = $admin['nombre'];
            // 44: Redirigimos al panel ya logueado
            header("Location: admin_panel.php");
            // 45: Bye
            exit;
        // 46: Cerramos el if del password
        }
    // 47: Cerramos el if del usuario encontrado
    }
    // 48: Si algo falló, mensaje de error
    $_SESSION['mensaje'] = "❌ Correo o contraseña incorrectos.";
    // 49: Cerramos el statement
    $stmt->close();
    // 50: Recargamos
    header("Location: admin_panel.php");
    // 51: Bye
    exit;
// 52: Cerramos el if del login
}

// 53: Comentario: Si ya está logueado, mostramos el panel
// Si admin logueado -> panel
// 54: Checamos si existe la variable de sesión admin_id
if(isset($_SESSION['admin_id'])){

// 55: Comentario: Agregar marca nueva
// Agregar marca CON IMAGEN
// 56: Si mandaron el form de nueva marca...
if(isset($_POST['nueva_marca'])){
    // 57: Limpiamos el nombre de la marca
    $nombre_marca = trim($_POST['nombre_marca']);
    // 58: Inicializamos la variable de imagen vacía
    $imagen_marca = '';

    // 59: Si subieron un archivo y no hubo error...
    if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
        // 60: Le ponemos nombre único con el tiempo actual
        $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
        // 61: Movemos el archivo a la carpeta uploads
        move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
    // 62: Cerramos el if de la imagen
    }

    // 63: Si el nombre no está vacío...
    if($nombre_marca !== ''){
        // 64: Preparamos el insert pa' la marca
        $stmt = $conexion->prepare("INSERT INTO marcas(nombre, imagen) VALUES(?, ?)");
        // 65: Pasamos nombre e imagen
        $stmt->bind_param("ss", $nombre_marca, $imagen_marca);
        // 66: Ejecutamos
        $stmt->execute();
        // 67: Cerramos
        $stmt->close();
        // 68: Mensaje de éxito
        $_SESSION['mensaje'] = "✅ Marca agregada correctamente.";
        // 69: Recargamos
        header("Location: admin_panel.php");
        // 70: Bye
        exit;
    // 71: Cerramos el if del nombre
    }
// 72: Cerramos el if de nueva marca
}

// 73: Comentario: Actualizar una marca existente
// Actualizar marca
// 74: Si mandaron actualizar marca...
if(isset($_POST['actualizar_marca'])){
    // 75: Convertimos el ID a entero por seguridad
    $id = intval($_POST['id_marca']);
    // 76: Limpiamos el nombre
    $nombre_marca = trim($_POST['nombre_marca']);
    // 77: Por defecto dejamos la imagen que ya tenía
    $imagen_marca = $_POST['imagen_actual']; // Mantener imagen actual por defecto

    // 78: Comentario: Si suben imagen nueva
    // Si se sube nueva imagen
    // 79: Checamos si hay archivo nuevo sin errores
    if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
        // 80: Generamos nombre nuevo
        $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
        // 81: Subimos la imagen nueva
        move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
        
        // 82: Comentario: Borramos la vieja pa' no llenar basura
        // Eliminar imagen anterior si existe
        // 83: Si había imagen vieja y el archivo existe...
        if($_POST['imagen_actual'] && file_exists("uploads/".$_POST['imagen_actual'])){
            // 84: La borramos del servidor
            unlink("uploads/".$_POST['imagen_actual']);
        // 85: Cerramos if de borrar
        }
    // 86: Cerramos if de nueva imagen
    }

    // 87: Preparamos el update
    $stmt = $conexion->prepare("UPDATE marcas SET nombre=?, imagen=? WHERE id=?");
    // 88: Pasamos los datos
    $stmt->bind_param("ssi", $nombre_marca, $imagen_marca, $id);
    // 89: Ejecutamos
    $stmt->execute();
    // 90: Cerramos
    $stmt->close();
    // 91: Mensaje de éxito
    $_SESSION['mensaje'] = "✅ Marca actualizada correctamente.";
    // 92: Recargamos
    header("Location: admin_panel.php");
    // 93: Bye
    exit;
// 94: Cerramos if de actualizar
}

    // 95: Comentario: Eliminar marca
    // Eliminar marca
    // 96: Si piden eliminar marca por GET...
    if(isset($_GET['eliminar_marca'])){
        // 97: Aseguramos que el ID sea número
        $id = intval($_GET['eliminar_marca']);
        // 98: Ejecutamos el delete directo
        $conexion->query("DELETE FROM marcas WHERE id=$id");
        // 99: Mensaje de éxito
        $_SESSION['mensaje'] = "✅ Marca eliminada.";
        // 100: Recargamos
        header("Location: admin_panel.php");
        // 101: Bye
        exit;
    // 102: Cerramos if eliminar
    }

    // 103: Comentario: Eliminar TODAS las piezas, peligroso
    // Eliminar TODAS las piezas
    // 104: Si confirman eliminar todas las piezas...
    if(isset($_POST['eliminar_todas_piezas'])){
        // 105: Borramos todo de la tabla piezas
        $conexion->query("DELETE FROM piezas");
        // 106: Mensaje de limpieza total
        $_SESSION['mensaje'] = "✅ Todas las piezas han sido eliminadas.";
        // 107: Recargamos
        header("Location: admin_panel.php");
        // 108: Bye
        exit;
    // 109: Cerramos if
    }

    // 110: Comentario: Eliminar TODAS las marcas
    // Eliminar TODAS las marcas
    // 111: Si confirman eliminar todas las marcas...
    if(isset($_POST['eliminar_todas_marcas'])){
        // 112: Borramos todo de la tabla marcas
        $conexion->query("DELETE FROM marcas");
        // 113: Mensaje de limpieza
        $_SESSION['mensaje'] = "✅ Todas las marcas han sido eliminadas.";
        // 114: Recargamos
        header("Location: admin_panel.php");
        // 115: Bye
        exit;
    // 116: Cerramos if
    }

    // 117: Comentario: Agregar una pieza nueva
    // Agregar pieza
    // 118: Si mandan el form de agregar pieza...
    if(isset($_POST['agregar_pieza'])){
        // 119: Limpiamos nombre
        $nombre = trim($_POST['nombre']);
        // 120: Limpiamos descripción
        $descripcion = trim($_POST['descripcion']);
        // 121: Convertimos precio a flotante
        $precio = floatval($_POST['precio']);
        // 122: Cantidad a entero
        $cantidad = intval($_POST['cantidad']);
        // 123: ID de marca a entero
        $marca_id = intval($_POST['marca_id']);
        // 124: Imagen vacía por defecto
        $imagen = '';

        // 125: Si suben imagen sin errores...
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            // 126: Generamos nombre único
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            // 127: Guardamos la imagen
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
        // 128: Cerramos if imagen
        }

        // 129: Preparamos el insert de la pieza
        $stmt = $conexion->prepare("INSERT INTO piezas(nombre, descripcion, precio, cantidad, marca_id, imagen) VALUES(?,?,?,?,?,?)");
        // 130: Pasamos todos los parámetros
        $stmt->bind_param("ssdiis",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen);
        // 131: Ejecutamos
        $stmt->execute();
        
        // NUEVO: Obtener el ID de la pieza recién creada
        $pieza_id = $stmt->insert_id;
        $stmt->close();

        // NUEVO: Manejo de galería de imágenes
        if(isset($_FILES['galeria'])){
            $total = count($_FILES['galeria']['name']);
            for($i=0; $i<$total; $i++){
                if($_FILES['galeria']['error'][$i] == 0){
                    $img_gal = time().'_'.$i.'_'.basename($_FILES['galeria']['name'][$i]);
                    move_uploaded_file($_FILES['galeria']['tmp_name'][$i], "uploads/".$img_gal);
                    $stmt_gal = $conexion->prepare("INSERT INTO piezas_imagenes(pieza_id, imagen) VALUES(?, ?)");
                    $stmt_gal->bind_param("is", $pieza_id, $img_gal);
                    $stmt_gal->execute();
                    $stmt_gal->close();
                }
            }
        }

        // 133: Mensaje de éxito
        $_SESSION['mensaje'] = "✅ Pieza agregada correctamente.";
        // 134: Recargamos
        header("Location: admin_panel.php");
        // 135: Bye
        exit;
    // 136: Cerramos if agregar
    }

    // NUEVO: Eliminar imagen de galería
    if(isset($_GET['eliminar_img_gal'])){
        $id_img = intval($_GET['eliminar_img_gal']);
        $res_img = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE id=$id_img");
        if($row_img = $res_img->fetch_assoc()){
            if(file_exists("uploads/".$row_img['imagen'])){
                unlink("uploads/".$row_img['imagen']);
            }
        }
        $conexion->query("DELETE FROM piezas_imagenes WHERE id=$id_img");
        $_SESSION['mensaje'] = "✅ Imagen de galería eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

 // 137: Comentario: Eliminar pieza (repetido abajo, pero aquí está)
 // Eliminar usuario 
    // 138: Si piden eliminar pieza...
    if(isset($_GET['eliminar_pieza'])){
        // 139: ID a entero
        $id = intval($_GET['eliminar_pieza']);
        // 140: Borramos la pieza
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        // 141: Mensaje
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        // 142: Recargamos
        header("Location: admin_panel.php");
        // 143: Bye
        exit;
    // 144: Cerramos if
    }

    // 145: Comentario: Actualizar pieza existente
    // Actualizar pieza
    // 146: Si mandan actualizar pieza...
    if(isset($_POST['actualizar_pieza'])){
        // 147: ID a entero
        $id = intval($_POST['id']);
        // 148: Nombre limpio
        $nombre = trim($_POST['nombre']);
        // 149: Descripción limpia
        $descripcion = trim($_POST['descripcion']);
        // 150: Precio flotante
        $precio = floatval($_POST['precio']);
        // 151: Cantidad entero
        $cantidad = intval($_POST['cantidad']);
        // 152: Marca entero
        $marca_id = intval($_POST['marca_id']);

        // 153: Comentario: Si hay imagen nueva
        // Si se sube nueva imagen
        // 154: Checamos archivo
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            // 155: Nombre nuevo
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            // 156: Subimos
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
            // 157: Preparamos update CON imagen
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=?, imagen=? WHERE id=?");
            // 158: Bind params con imagen
            $stmt->bind_param("ssdiisi",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen,$id);
        // 159: Si no hay imagen nueva...
        } else {
            // 160: Preparamos update SIN imagen
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=? WHERE id=?");
            // 161: Bind params sin imagen
            $stmt->bind_param("ssdiii",$nombre,$descripcion,$precio,$cantidad,$marca_id,$id);
        // 162: Cerramos else
        }
        
        // 163: Ejecutamos el update
        $stmt->execute();
        $stmt->close();

        // NUEVO: Subir más imágenes a la galería
        if(isset($_FILES['galeria'])){
            $total = count($_FILES['galeria']['name']);
            for($i=0; $i<$total; $i++){
                if($_FILES['galeria']['error'][$i] == 0){
                    $img_gal = time().'_'.$i.'_'.basename($_FILES['galeria']['name'][$i]);
                    move_uploaded_file($_FILES['galeria']['tmp_name'][$i], "uploads/".$img_gal);
                    $stmt_gal = $conexion->prepare("INSERT INTO piezas_imagenes(pieza_id, imagen) VALUES(?, ?)");
                    $stmt_gal->bind_param("is", $id, $img_gal);
                    $stmt_gal->execute();
                    $stmt_gal->close();
                }
            }
        }

        // 165: Mensaje
        $_SESSION['mensaje'] = "✅ Pieza actualizada correctamente.";
        // 166: Recargamos
        header("Location: admin_panel.php");
        // 167: Bye
        exit;
    // 168: Cerramos if actualizar
    }

    // 169: Comentario: Eliminar pieza (otra vez, por si acaso)
    // Eliminar pieza
    // 170: Si piden eliminar...
    if(isset($_GET['eliminar_pieza'])){
        // 171: ID a entero
        $id = intval($_GET['eliminar_pieza']);
        // 172: Borramos
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        // 173: Mensaje
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        // 174: Recargamos
        header("Location: admin_panel.php");
        // 175: Bye
        exit;
    // 176: Cerramos if
    }

    // 177: Comentario: Consultas para llenar las tablas
    // Listar marcas y piezas
    // 178: Traemos todas las marcas ordenadas por nombre
    $marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
    // 179: Traemos las piezas con el nombre de su marca (JOIN)
    $piezas = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
    
// 180: Cerramos el if de admin logueado
}
?>
<!-- 181: Empieza el HTML -->
<!DOCTYPE html>
<!-- 182: Idioma español -->
<html lang="es">
<!-- 183: Head del documento -->
<head>
<!-- 184: Codificación UTF-8 pa' los acentos -->
<meta charset="UTF-8">
<!-- 185: Viewport pa' que se vea bien en cel -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- 186: Título de la pestaña -->
<title>Admin MRMC</title>
<!-- 187: Vinculamos el CSS que acabamos de enchular -->
<link rel="stylesheet" href="admin.css">
<!-- 188: Cerramos head -->
</head>
<!-- 189: Body del documento -->
<body>

<!-- 190: Si NO está logueado el admin... -->
<?php if(!isset($_SESSION['admin_id'])): ?>
<!-- 191: Comentario: Sección de Login/Registro -->
<!-- Login / Crear Admin -->
<!-- 192: Main container -->
<main>
<!-- 193: Si hay mensaje que mostrar... -->
<?php if($mensaje): ?>
<!-- 194: Modal del mensaje -->
<div class="modal-mensaje exito">
<!-- 195: Contenido del modal -->
<div class="modal-contenido">
<!-- 196: Título del modal -->
<h2>Mensaje</h2>
<!-- 197: El mensaje en sí, protegido con htmlspecialchars -->
<p><?= htmlspecialchars($mensaje) ?></p>
<!-- 198: Botón pa' cerrar el modal con JS inline -->
<button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
<!-- 199: Cierre div contenido -->
</div>
<!-- 200: Cierre div modal -->
</div>
<!-- 201: Fin del if mensaje -->
<?php endif; ?>

<!-- 202: Sección del formulario de login -->
<section class="formulario">
<!-- 203: Título login -->
<h2>Login Admin</h2>
<!-- 204: Formulario POST -->
<form method="post">
<!-- 205: Input correo -->
<input type="email" name="correo" placeholder="Correo" required>
<!-- 206: Input contraseña -->
<input type="password" name="contrasena" placeholder="Contraseña" required>
<!-- 207: Botón submit login -->
<button type="submit" name="login_admin">Iniciar Sesión</button>
<!-- 208: Cierre form -->
<header>
<div class="formulario">
<a href="dashboard-piezas.php">dashboard</a>
</header>
</div>

</form>
<!-- 209: Cierre section -->
</section>

<!-- 210: Sección crear admin -->
<section class="formulario">
<!-- 211: Título crear -->
<h2>Crear Nuevo Admin</h2>
<!-- 212: Formulario POST -->
<form method="post">
<!-- 213: Input nombre -->
<input type="text" name="nombre" placeholder="Nombre" required>
<!-- 214: Input correo -->
<input type="email" name="correo" placeholder="Correo" required>
<!-- 215: Input contraseña -->
<input type="password" name="contrasena" placeholder="Contraseña" required>
<!-- 216: Input clave creador (la secreta) -->
<input type="text" name="clave_creador" placeholder="Clave del creador" required>
<!-- 217: Botón submit crear -->
<button type="submit" name="crear_admin">Crear Admin</button>
<!-- 218: Cierre form -->
</form>
<!-- 219: Cierre section -->
</section>
<!-- 220: Cierre main -->
</main>

<!-- 221: Si SÍ está logueado (Else del if principal) -->
<?php else: ?>
<!-- 222: Comentario: Panel principal -->
<!-- Panel Admin -->
<!-- 223: Header del panel -->
<header>
<!-- 224: Título del panel -->
<h1>Panel de Administración MRMP</h1>
<!-- 225: Botón de cerrar sesión -->
<a href="dashboard-piezas.php">dashboard</a>
<a href="?logout" style="color:#ff0000;">Cerrar sesión</a>
<!-- 226: Cierre header -->
</header>

<!-- 227: Main del panel -->
<main>
<!-- 228: Si hay mensaje... -->
<?php if($mensaje): ?>
<!-- 229: Modal mensaje -->
<div class="modal-mensaje exito">
<!-- 230: Contenido modal -->
<div class="modal-contenido">
<!-- 231: Título -->
<h2>Mensaje</h2>
<!-- 232: Texto mensaje -->
<p><?= htmlspecialchars($mensaje) ?></p>
<!-- 233: Botón cerrar -->
<button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
<!-- 234: Cierre div -->
</div>
<!-- 235: Cierre div -->
</div>
<!-- 236: Fin if mensaje -->
<?php endif; ?>


<!-- 237: Acciones rápidas (Eliminar todo) -->
<div class="acciones-rapidas">
    <!-- 238: Formulario con confirmación JS -->
    <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
        <!-- 239: Botón eliminar todas las marcas -->
        <button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
    <!-- 240: Cierre form -->
    </form>
<!-- 241: Cierre div -->
</div>

<!-- 242: Sección gestionar marcas -->
<section class="formulario">
<!-- 243: Título -->
<h2>Gestionar Marcas</h2>
<!-- 244: Formulario multipart para subir archivos -->
<form method="post" enctype="multipart/form-data">
    <!-- 245: Input nombre marca -->
    <input type="text" name="nombre_marca" placeholder="Nombre de la marca" required>
    <!-- 246: Input archivo imagen -->
    <input type="file" name="imagen_marca" accept="image/*">
    <!-- 247: Nota informativa -->
    <small>Formatos: PNG, JPG, JPEG. Las imágenes se guardarán en uploads/</small>
    <!-- 248: Botón agregar -->
    <button type="submit" name="nueva_marca">Agregar Marca</button>
<!-- 249: Cierre form -->
</form>

<!-- 250: Otra vez acciones rápidas (repetido en el original) -->
<div class="acciones-rapidas">
    <!-- 251: Form eliminar todas -->
    <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
        <!-- 252: Botón eliminar -->
        <button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
    <!-- 253: Cierre form -->
    </form>
<!-- 254: Cierre div -->
</div>

<!-- 255: Título lista marcas -->
<h3>Marcas Registradas</h3>
<!-- 256: Lista UL -->
<ul class="lista-marcas">
<!-- 257: PHP para iterar marcas -->
<?php 
// 258: Consulta de marcas
$marcas_lista = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
// 259: Loop while
while($m = $marcas_lista->fetch_assoc()): 
?>
    <!-- 260: Item de lista -->
    <li class="marca-item">
        <!-- 261: Info marca -->
        <div class="marca-info">
            <!-- 262: Si tiene imagen... -->
            <?php if($m['imagen']): ?>
                <!-- 263: Mostrar imagen -->
                <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" alt="<?= htmlspecialchars($m['nombre']) ?>" width="50" style="margin-right: 10px;">
            <!-- 264: Fin if imagen -->
            <?php endif; ?>
            <!-- 265: Nombre marca -->
            <strong><?= htmlspecialchars($m['nombre']) ?></strong>
        <!-- 266: Cierre div info -->
        </div>
        <!-- 267: Acciones editar/eliminar -->
        <div class="marca-acciones">
            <!-- 268: Link editar (ancla) -->
            <a href="#editar-marca-<?= $m['id'] ?>" style="color: #007bff;">Editar</a> - 
            <!-- 269: Link eliminar con confirmación -->
            <a href="?eliminar_marca=<?= $m['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta marca?')">Eliminar</a>
        <!-- 270: Cierre div acciones -->
        </div>
        
        <!-- 271: Comentario: Formulario de edición oculto/visible por ID -->
        <!-- Formulario de edición para cada marca -->
        <!-- 272: Div contenedor edición -->
        <div id="editar-marca-<?= $m['id'] ?>" class="form-edicion-marca">
            <!-- 273: Título edición -->
            <h4>Editar Marca: <?= htmlspecialchars($m['nombre']) ?></h4>
            <!-- 274: Formulario edición -->
            <form method="post" enctype="multipart/form-data">
                <!-- 275: ID oculto -->
                <input type="hidden" name="id_marca" value="<?= $m['id'] ?>">
                <!-- 276: Imagen actual oculta -->
                <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($m['imagen']) ?>">
                
                <!-- 277: Input nombre -->
                <input type="text" name="nombre_marca" value="<?= htmlspecialchars($m['nombre']) ?>" placeholder="Nombre de la marca" required>
                
                <!-- 278: Div previsualización imagen -->
                <div style="margin: 10px 0;">
                    <!-- 279: Si hay imagen... -->
                    <?php if($m['imagen']): ?>
                        <!-- 280: Mostrarla -->
                        <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" alt="Imagen actual" width="80" style="display: block; margin-bottom: 5px;">
                        <!-- 281: Texto ruta -->
                        <small>Imagen actual (uploads/<?= htmlspecialchars($m['imagen']) ?>)</small>
                    <!-- 282: Si no... -->
                    <?php else: ?>
                        <!-- 283: Texto no hay -->
                        <small>No hay imagen</small>
                    <!-- 284: Fin if -->
                    <?php endif; ?>
                <!-- 285: Cierre div -->
                </div>
                
                <!-- 286: Input file nueva imagen -->
                <input type="file" name="imagen_marca" accept="image/*">
                <!-- 287: Nota -->
                <small>Dejar vacío para mantener la imagen actual</small>
                
                <!-- 288: Botón actualizar -->
                <button type="submit" name="actualizar_marca">Actualizar Marca</button>
            <!-- 289: Cierre form -->
            </form>
        <!-- 290: Cierre div edición -->
        </div>
    <!-- 291: Cierre li -->
    </li>
<!-- 292: Fin while -->
<?php endwhile; ?>
<!-- 293: Cierre ul -->
</ul>
<!-- 294: Cierre section -->
</section>

<!-- 295: Acciones rápidas (otra vez) -->
<div class="acciones-rapidas">
<!-- 296: Form eliminar -->
<form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
<!-- 297: Botón eliminar -->
<button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
<!-- 298: Cierre form -->
</form>
<!-- 299: Cierre div -->
</div>

<!-- 300: Lista simple de marcas (parece redundante pero estaba en el original) -->
<h3>Marcas Registradas</h3>
<!-- 301: UL -->
<ul>
<!-- 302: While marcas -->
<?php while($m = $marcas->fetch_assoc()): ?>
<!-- 303: LI con nombre y eliminar -->
<li><?= htmlspecialchars($m['nombre']) ?> - <a href="?eliminar_marca=<?= $m['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta marca?')">Eliminar</a></li>
<!-- 304: End while -->
<?php endwhile; ?>
<!-- 305: Cierre ul -->
</ul>
<!-- 306: Cierre section -->
</section>

<!-- 307: Sección agregar pieza -->
<section class="formulario">
<!-- 308: Título -->
<h2>Agregar Pieza</h2>
<!-- 309: Formulario multipart -->
<form method="post" enctype="multipart/form-data">
<!-- 310: Input nombre -->
<input type="text" name="nombre" placeholder="Nombre de la pieza" required>
<!-- 311: Textarea descripción -->
<textarea name="descripcion" placeholder="Descripción" rows="3" required></textarea>
<!-- 312: Input precio -->
<input type="number" step="0.01" name="precio" placeholder="Precio" required>
<!-- 313: Input cantidad -->
<input type="number" name="cantidad" placeholder="Cantidad" required>
<!-- 314: Select marca -->
<select name="marca_id" required>
<!-- 315: Opción default -->
<option value="">Selecciona marca</option>
<!-- 316: PHP para opciones de marcas -->
<?php 
// 317: Query marcas
$marcas_sel = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
// 318: While marcas
while($m2 = $marcas_sel->fetch_assoc()): ?>
<!-- 319: Option value -->
<option value="<?= $m2['id'] ?>"><?= htmlspecialchars($m2['nombre']) ?></option>
<!-- 320: End while -->
<?php endwhile; ?>
<!-- 321: Cierre select -->
</select>
<!-- 322: Input imagen -->
<label>Imagen Principal:</label>
<input type="file" name="imagen">
<!-- NUEVO: Input galería -->
<label>Galería de Imágenes (Selecciona varias):</label>
<input type="file" name="galeria[]" multiple accept="image/*">
<!-- 323: Botón agregar -->
<button type="submit" name="agregar_pieza">Agregar Pieza</button>
<!-- 324: Cierre form -->
</form>

<!-- 325: Acciones rápidas piezas -->
<div class="acciones-rapidas">
<!-- 326: Form eliminar todas piezas -->
<form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las piezas?')">
<!-- 327: Botón eliminar -->
<button type="submit" name="eliminar_todas_piezas" class="eliminar">Eliminar Todas las Piezas</button>
<!-- 328: Cierre form -->
</form>
<!-- 329: Cierre div -->
</div>

<!-- 330: Tabla de piezas -->
<h3>Piezas Registradas</h3>
<!-- 331: Table -->
<table>
<!-- 332: Row headers -->
<tr>
<!-- 333: TH ID -->
<th>ID</th>
<!-- 334: TH Nombre -->
<th>Nombre</th>
<!-- 335: TH Descripción -->
<th>Descripción</th>
<!-- 336: TH Marca -->
<th>Marca</th>
<!-- 337: TH Precio -->
<th>Precio</th>
<!-- 338: TH Cantidad -->
<th>Cantidad</th>
<!-- 339: TH Imagen -->
<th>Imagen</th>
<!-- 340: TH Acciones -->
<th>Acciones</th>
<!-- 341: Cierre row -->
</tr>
<!-- 342: PHP loop piezas -->
<?php 
// 343: Query piezas con join
$piezas_lista = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
// 344: While piezas
while($p = $piezas_lista->fetch_assoc()): 
?>
<!-- 345: Row datos -->
<tr>
<!-- 346: TD ID -->
<td><?= $p['id'] ?></td>
<!-- 347: TD Nombre -->
<td><?= htmlspecialchars($p['nombre']) ?></td>
<!-- 348: TD Descripción -->
<td><?= htmlspecialchars($p['descripcion']) ?></td>
<!-- 349: TD Marca -->
<td><?= htmlspecialchars($p['marca_nombre']) ?></td>
<!-- 350: TD Precio -->
<td>$<?= number_format($p['precio'],2) ?></td>
<!-- 351: TD Cantidad -->
<td><?= intval($p['cantidad']) ?></td>
<!-- 352: TD Imagen -->
<td><?php if($p['imagen']): ?><img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" width="50"><?php endif;?></td>
<!-- 353: TD Acciones -->
<td>
<!-- Botón editar con JS -->
<button onclick="toggleEdit(<?= $p['id'] ?>)" style="cursor:pointer; color:blue; background:none; border:none; text-decoration:underline;">Editar</button>
 - 
<!-- 354: Link eliminar -->
<a href="?eliminar_pieza=<?= $p['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta pieza?')">Eliminar</a>
<!-- 355: Cierre TD -->
</td>
<!-- 356: Cierre TR -->
</tr>
<!-- 357: Comentario: Fila extra para edición -->
<!-- Formulario de edición para cada pieza -->
<!-- 358: TR edición (OCULTO POR DEFECTO) -->
<tr id="edit-row-<?= $p['id'] ?>" style="display:none; background-color: #f9f9f9;">
<!-- 359: TD colspan 8 -->
<td colspan="8">
<!-- 360: Div edición -->
<div class="form-edicion" style="padding: 20px; border: 1px solid #ddd;">
<!-- 361: Título edición -->
<h4>Editar Pieza: <?= htmlspecialchars($p['nombre']) ?></h4>
<!-- 362: Form edición -->
<form method="post" enctype="multipart/form-data">
<!-- 363: Hidden ID -->
<input type="hidden" name="id" value="<?= $p['id'] ?>">
<!-- 364: Input nombre -->
<input type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>" placeholder="Nombre" required>
<!-- 365: Textarea descripción -->
<textarea name="descripcion" placeholder="Descripción" rows="3" required><?= htmlspecialchars($p['descripcion']) ?></textarea>
<!-- 366: Input precio -->
<input type="number" step="0.01" name="precio" value="<?= $p['precio'] ?>" placeholder="Precio" required>
<!-- 367: Input cantidad -->
<input type="number" name="cantidad" value="<?= $p['cantidad'] ?>" placeholder="Cantidad" required>
<!-- 368: Select marca -->
<select name="marca_id" required>
<!-- 369: Option default -->
<option value="">Selecciona marca</option>
<!-- 370: PHP marcas edit -->
<?php 
// 371: Query marcas
$marcas_edit = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
// 372: While marcas
while($m3 = $marcas_edit->fetch_assoc()): 
?>
<!-- 373: Option con selected -->
<option value="<?= $m3['id'] ?>" <?= $m3['id'] == $p['marca_id'] ? 'selected' : '' ?>>
<!-- 374: Nombre marca -->
<?= htmlspecialchars($m3['nombre']) ?>
<!-- 375: Cierre option -->
</option>
<!-- 376: End while -->
<?php endwhile; ?>
<!-- 377: Cierre select -->
</select>
<!-- 378: Input file -->
<label>Imagen Principal:</label>
<input type="file" name="imagen">
<small>Dejar vacío para mantener la imagen actual</small><br>

<!-- NUEVO: Galería en edición -->
<label>Agregar más imágenes a la galería:</label>
<input type="file" name="galeria[]" multiple accept="image/*">

<!-- Mostrar imágenes de galería existentes -->
<div style="margin-top:10px;">
    <h5>Galería Actual:</h5>
    <?php
    $gal_res = $conexion->query("SELECT * FROM piezas_imagenes WHERE pieza_id=".$p['id']);
    while($gal = $gal_res->fetch_assoc()):
    ?>
    <div style="display:inline-block; margin:5px; text-align:center;">
        <img src="uploads/<?= $gal['imagen'] ?>" width="60" height="60" style="object-fit:cover; border:1px solid #ccc;">
        <br>
        <a href="?eliminar_img_gal=<?= $gal['id'] ?>" style="color:red; font-size:12px;" onclick="return confirm('¿Borrar esta imagen?')">Borrar</a>
    </div>
    <?php endwhile; ?>
</div>

<!-- 380: Botón actualizar -->
<button type="submit" name="actualizar_pieza">Actualizar Pieza</button>
<button type="button" onclick="toggleEdit(<?= $p['id'] ?>)" style="background-color: #6c757d;">Cancelar</button>
<!-- 381: Cierre form -->
</form>
<!-- 382: Cierre div -->
</div>
<!-- 383: Cierre TD -->
</td>
<!-- 384: Cierre TR -->
</tr>
<!-- 385: End while piezas -->
<?php endwhile; ?>
<!-- 386: Cierre table -->
</table>
<!-- 387: Cierre section -->
</section>
<!-- 388: Cierre main -->
</main>

<!-- 389: Footer -->
<footer style="text-align: center; margin-top: 20px; color: #888;">
<!-- 390: Copyright con año dinámico -->
&copy; <?= date('Y') ?> Mexican Racing Motor Parts
<!-- 391: Cierre footer -->
</footer>

<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<!-- 392: Fin del if principal (admin logueado) -->
<?php endif; ?>
<!-- 393: Cierre body -->
</body>
<!-- 394: Cierre html -->
</html>