<?php
// session_start: Inicia sesión para persistencia de usuario
session_start();
// require_once: Carga archivo de conexión a la base de datos
require_once "conexion.php";

// Clave secreta para crear nuevos administradores
$claveCreador = "CBTIS52";
// Obtiene el mensaje de la sesión si existe, o vacío si no
$mensaje = $_SESSION['mensaje'] ?? '';
// Elimina el mensaje para que no persista tras recargar
unset($_SESSION['mensaje']);

// Bloque de Logout (Cerrar sesión)
// Verifica si hay parámetro logout en la URL
if(isset($_GET['logout'])){
    // Destruye la sesión actual
    session_destroy();
    // Redirecciona al panel (ahora limpio)
    header("Location: admin_panel.php");
    // Detiene ejecución
    exit;
}

// Bloque de Crear Admin
// Verifica variable POST del formulario crear admin
if(isset($_POST['crear_admin'])){
    // Limpia espacios nombre
    $nombre = trim($_POST['nombre']);
    // Limpia espacios correo
    $correo = trim($_POST['correo']);
    // Contraseña sin trim (puede llevar espacios)
    $contrasena = $_POST['contrasena'];
    // Clave de seguridad proporcionada
    $clave = $_POST['clave_creador'];

    // Valida clave maestra
    if($clave !== $claveCreador){
        $_SESSION['mensaje'] = "❌ Clave del creador incorrecta.";
    } 
    // Valida que campos no estén vacíos
    elseif($nombre && $correo && $contrasena){
        // Hash seguro de contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        // Prepara inserción SQL
        $stmt = $conexion->prepare("INSERT INTO admins(nombre, correo, contrasena_hash) VALUES(?,?,?)");
        // Bind parámetros (string, string, string)
        $stmt->bind_param("sss",$nombre,$correo,$hash);
        // Ejecuta query
        $stmt->execute();
        // Cierra statement
        $stmt->close();
        $_SESSION['mensaje'] = "✅ Admin creado correctamente.";
    }
    // Recarga página
    header("Location: admin_panel.php");
    exit;
}

// Bloque de Login Admin
// Verifica POST login
if(isset($_POST['login_admin'])){
    // Limpia correo
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];

    // Prepara consulta buscar admin por correo
    $stmt = $conexion->prepare("SELECT * FROM admins WHERE correo=?");
    $stmt->bind_param("s",$correo);
    $stmt->execute();
    $res = $stmt->get_result();

    // Si encuentra resultado único
    if($res && $res->num_rows==1){
        $admin = $res->fetch_assoc();
        // Verifica hash contraseña
        if(password_verify($contrasena, $admin['contrasena_hash'])){
            // Guarda ID en sesión
            $_SESSION['admin_id'] = $admin['id'];
            // Guarda Nombre en sesión
            $_SESSION['admin_nombre'] = $admin['nombre'];
            // Redirecciona al panel logueado
            header("Location: admin_panel.php");
            exit;
        }
    }
    // Si falla
    $_SESSION['mensaje'] = "❌ Correo o contraseña incorrectos.";
    $stmt->close();
    header("Location: admin_panel.php");
    exit;
}

// Bloque Principal: Usuario Logueado
// Si existe sesión admin, procesa lógica del panel
if(isset($_SESSION['admin_id'])){

    // Agregar Nueva Marca
    if(isset($_POST['nueva_marca'])){
        $nombre_marca = trim($_POST['nombre_marca']);
        $imagen_marca = '';

        // Procesa subida imagen marca
        if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
            // Nombre único con timestamp
            $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
            // Mueve archivo a uploads
            move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
        }

        if($nombre_marca !== ''){
            // Insertar marca en BD
            $stmt = $conexion->prepare("INSERT INTO marcas(nombre, imagen) VALUES(?, ?)");
            $stmt->bind_param("ss", $nombre_marca, $imagen_marca);
            $stmt->execute();
            $stmt->close();
            $_SESSION['mensaje'] = "✅ Marca agregada correctamente.";
            header("Location: admin_panel.php");
            exit;
        }
    }

    // Actualizar Marca Existente
    if(isset($_POST['actualizar_marca'])){
        $id = intval($_POST['id_marca']);
        $nombre_marca = trim($_POST['nombre_marca']);
        $imagen_marca = $_POST['imagen_actual']; // Mantiene actual por defecto

        // Si sube nueva imagen
        if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
            $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
            move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
            
            // Borra imagen anterior para no acumular basura
            if($_POST['imagen_actual'] && file_exists("uploads/".$_POST['imagen_actual'])){
                unlink("uploads/".$_POST['imagen_actual']);
            }
        }

        // Update SQL
        $stmt = $conexion->prepare("UPDATE marcas SET nombre=?, imagen=? WHERE id=?");
        $stmt->bind_param("ssi", $nombre_marca, $imagen_marca, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "✅ Marca actualizada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar Marca
    if(isset($_GET['eliminar_marca'])){
        $id = intval($_GET['eliminar_marca']);
        $conexion->query("DELETE FROM marcas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Marca eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar TODAS las piezas (Peligro)
    if(isset($_POST['eliminar_todas_piezas'])){
        $conexion->query("DELETE FROM piezas");
        $_SESSION['mensaje'] = "✅ Todas las piezas han sido eliminadas.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar TODAS las marcas (Peligro)
    if(isset($_POST['eliminar_todas_marcas'])){
        $conexion->query("DELETE FROM marcas");
        $_SESSION['mensaje'] = "✅ Todas las marcas han sido eliminadas.";
        header("Location: admin_panel.php");
        exit;
    }

    // Agregar Nueva Pieza
    if(isset($_POST['agregar_pieza'])){
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $cantidad = intval($_POST['cantidad']);
        $marca_id = intval($_POST['marca_id']);
        $imagen = '';

        // Subir imagen principal
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
        }

        // Insertar Pieza
        $stmt = $conexion->prepare("INSERT INTO piezas(nombre, descripcion, precio, cantidad, marca_id, imagen) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssdiis",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen);
        $stmt->execute();
        
        // Obtener ID generado para galería
        $pieza_id = $stmt->insert_id;
        $stmt->close();

        // Procesar Galería (múltiples archivos)
        if(isset($_FILES['galeria'])){
            $total = count($_FILES['galeria']['name']);
            for($i=0; $i<$total; $i++){
                if($_FILES['galeria']['error'][$i] == 0){
                    $img_gal = time().'_'.$i.'_'.basename($_FILES['galeria']['name'][$i]);
                    move_uploaded_file($_FILES['galeria']['tmp_name'][$i], "uploads/".$img_gal);
                    // Insertar en tabla secundaria
                    $stmt_gal = $conexion->prepare("INSERT INTO piezas_imagenes(pieza_id, imagen) VALUES(?, ?)");
                    $stmt_gal->bind_param("is", $pieza_id, $img_gal);
                    $stmt_gal->execute();
                    $stmt_gal->close();
                }
            }
        }

        $_SESSION['mensaje'] = "✅ Pieza agregada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar una imagen de la galería de una pieza
    if(isset($_GET['eliminar_img_gal'])){
        $id_img = intval($_GET['eliminar_img_gal']);
        // Busca nombre archivo
        $res_img = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE id=$id_img");
        if($row_img = $res_img->fetch_assoc()){
            // Borra archivo físico
            if(file_exists("uploads/".$row_img['imagen'])){
                unlink("uploads/".$row_img['imagen']);
            }
        }
        // Borra registro BD
        $conexion->query("DELETE FROM piezas_imagenes WHERE id=$id_img");
        $_SESSION['mensaje'] = "✅ Imagen de galería eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar Pieza individual (y sus dependencias)
    if(isset($_GET['eliminar_pieza'])){
        $id = intval($_GET['eliminar_pieza']);
        
        // 1. Borrar dependencias en detalle_pedidos (si aplica)
        $conexion->query("DELETE FROM detalle_pedidos WHERE pieza_id=$id");
        
        // 2. Borrar imágenes de galería asociadas y sus archivos
        $res_imgs = $conexion->query("SELECT imagen FROM piezas_imagenes WHERE pieza_id=$id");
        while($img_row = $res_imgs->fetch_assoc()){
            if(file_exists("uploads/".$img_row['imagen'])){
                unlink("uploads/".$img_row['imagen']);
            }
        }
        $conexion->query("DELETE FROM piezas_imagenes WHERE pieza_id=$id");
        
        // 3. Borrar imagen principal y archivo
        $res_pieza = $conexion->query("SELECT imagen FROM piezas WHERE id=$id");
        if($pieza_data = $res_pieza->fetch_assoc()){
            if(!empty($pieza_data['imagen']) && file_exists("uploads/".$pieza_data['imagen'])){
                unlink("uploads/".$pieza_data['imagen']);
            }
        }
        
        // 4. Borrar pieza final
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Pieza eliminada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

    // Actualizar Pieza
    if(isset($_POST['actualizar_pieza'])){
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $cantidad = intval($_POST['cantidad']);
        $marca_id = intval($_POST['marca_id']);

        // Si se sube nueva imagen principal
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
            // Update con imagen
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=?, imagen=? WHERE id=?");
            $stmt->bind_param("ssdiisi",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen,$id);
        } else {
            // Update sin cambiar imagen
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=? WHERE id=?");
            $stmt->bind_param("ssdiii",$nombre,$descripcion,$precio,$cantidad,$marca_id,$id);
        }
        $stmt->execute();
        $stmt->close();

        // Agregar más imágenes a galería (opcional)
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

        $_SESSION['mensaje'] = "✅ Pieza actualizada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

    // Consultas para listar en tablas
    $marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
    $piezas = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin MRMC</title>
<link rel="stylesheet" href="admin.css"> <!-- Carga CSS externo -->
</head>
<body>

<!-- Si NO hay sesión de admin, mostrar login/registro -->
<?php if(!isset($_SESSION['admin_id'])): ?>
<main>
    <!-- Mostrar mensaje flash style -->
    <?php if($mensaje): ?>
    <div class="modal-mensaje exito">
    <div class="modal-contenido">
    <h2>Mensaje</h2>
    <p><?= htmlspecialchars($mensaje) ?></p>
    <button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
    </div>
    </div>
    <?php endif; ?>

    <!-- Formulario Login -->
    <section class="formulario">
    <h2>Login Admin</h2>
    <form method="post">
    <input type="email" name="correo" placeholder="Correo" required>
    <input type="password" name="contrasena" placeholder="Contraseña" required>
    <button type="submit" name="login_admin">Iniciar Sesión</button>
    <!-- Enlace dashborad -->
    <header>
    <div class="formulario">
    <a href="dashboard-piezas.php">dashboard</a>
    </header>
    </div>

    </form>
    </section>

    <!-- Formulario Registro -->
    <section class="formulario">
    <h2>Crear Nuevo Admin</h2>
    <form method="post">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="email" name="correo" placeholder="Correo" required>
    <input type="password" name="contrasena" placeholder="Contraseña" required>
    <input type="text" name="clave_creador" placeholder="Clave del creador" required>
    <button type="submit" name="crear_admin">Crear Admin</button>
    </form>
    </section>
</main>

<!-- Si SÍ hay sesión, mostrar Panel Completo -->
<?php else: ?>
<header>
<h1>Panel de Administración MRMP</h1>
<a href="gestionar_pedidos.php" style="color:#ff0000;">
     Gestionar Pedidos
</a> | 
<a href="dashboard-piezas.php" style="color:#ff0000;">
      Pagina de Piezas
</a> | 
<a href="?logout" style="color:#ff0000;">Cerrar sesión</a>
</header>
<main>
    <!-- Mensajes Flash -->
    <?php if($mensaje): ?>
    <div class="modal-mensaje exito">
    <div class="modal-contenido">
    <h2>Mensaje</h2>
    <p><?= htmlspecialchars($mensaje) ?></p>
    <button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
    </div>
    </div>
    <?php endif; ?>

    <!-- Botón Eliminar Marcas Masivo -->
    <div class="acciones-rapidas">
        <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
            <button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
        </form>
    </div>

    <!-- Gestión Marcas -->
    <section class="formulario">
    <h2>Gestionar Marcas</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="nombre_marca" placeholder="Nombre de la marca" required>
        <input type="file" name="imagen_marca" accept="image/*">
        <small>Formatos: PNG, JPG, JPEG. Las imágenes se guardarán en uploads/</small>
        <button type="submit" name="nueva_marca">Agregar Marca</button>
    </form>

    <!-- Botón duplicado eliminado -->

    <h3>Marcas Registradas</h3>
    <ul class="lista-marcas">
    <?php 
    // Query marcas
    $marcas_lista = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
    while($m = $marcas_lista->fetch_assoc()): 
    ?>
        <li class="marca-item">
            <div class="marca-info">
                <?php if($m['imagen']): ?>
                    <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" alt="<?= htmlspecialchars($m['nombre']) ?>" width="50" style="margin-right: 10px;">
                <?php endif; ?>
                <strong><?= htmlspecialchars($m['nombre']) ?></strong>
            </div>
            <div class="marca-acciones">
                <a href="#editar-marca-<?= $m['id'] ?>" style="color: #007bff;">Editar</a> - 
                <a href="?eliminar_marca=<?= $m['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta marca?')">Eliminar</a>
            </div>
            
            <!-- Bloque Edición Marca -->
            <div id="editar-marca-<?= $m['id'] ?>" class="form-edicion-marca">
                <h4>Editar Marca: <?= htmlspecialchars($m['nombre']) ?></h4>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id_marca" value="<?= $m['id'] ?>">
                    <input type="hidden" name="imagen_actual" value="<?= htmlspecialchars($m['imagen']) ?>">
                    
                    <input type="text" name="nombre_marca" value="<?= htmlspecialchars($m['nombre']) ?>" placeholder="Nombre de la marca" required>
                    
                    <div style="margin: 10px 0;">
                        <?php if($m['imagen']): ?>
                            <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" alt="Imagen actual" width="80" style="display: block; margin-bottom: 5px;">
                            <small>Imagen actual (uploads/<?= htmlspecialchars($m['imagen']) ?>)</small>
                        <?php else: ?>
                            <small>No hay imagen</small>
                        <?php endif; ?>
                    </div>
                    
                    <input type="file" name="imagen_marca" accept="image/*">
                    <small>Dejar vacío para mantener la imagen actual</small>
                    
                    <button type="submit" name="actualizar_marca">Actualizar Marca</button>
                </form>
            </div>
        </li>
    <?php endwhile; ?>
    </ul>
    </section>

    <!-- Botón Masivo duplicado -->
    <div class="acciones-rapidas">
    <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
    <button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
    </form>
    </div>

    <!-- Gestión Piezas -->
    <section class="formulario">
    <h2>Agregar Pieza</h2>
    <form method="post" enctype="multipart/form-data">
    <input type="text" name="nombre" placeholder="Nombre de la pieza" required>
    <textarea name="descripcion" placeholder="Descripción" rows="3" required></textarea>
    <input type="number" step="0.01" name="precio" placeholder="Precio" required>
    <input type="number" name="cantidad" placeholder="Cantidad" required>
    <select name="marca_id" required>
    <option value="">Selecciona marca</option>
    <?php 
    $marcas_sel = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
    while($m2 = $marcas_sel->fetch_assoc()): ?>
    <option value="<?= $m2['id'] ?>"><?= htmlspecialchars($m2['nombre']) ?></option>
    <?php endwhile; ?>
    </select>
    <label>Imagen Principal:</label>
    <input type="file" name="imagen">
    <label>Galería de Imágenes (Selecciona varias):</label>
    <input type="file" name="galeria[]" multiple accept="image/*">
    <button type="submit" name="agregar_pieza">Agregar Pieza</button>
    </form>

    <!-- Eliminar Todas Piezas -->
    <div class="acciones-rapidas">
    <form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las piezas?')">
    <button type="submit" name="eliminar_todas_piezas" class="eliminar">Eliminar Todas las Piezas</button>
    </form>
    </div>

    <!-- Tabla Piezas -->
    <h3>Piezas Registradas</h3>
    <table>
    <tr>
    <th>ID</th>
    <th>Nombre</th>
    <th>Descripción</th>
    <th>Marca</th>
    <th>Precio</th>
    <th>Cantidad</th>
    <th>Imagen</th>
    <th>Acciones</th>
    </tr>
    <?php 
    // Join para traer nombre de marca
    $piezas_lista = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
    while($p = $piezas_lista->fetch_assoc()): 
    ?>
    <tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['nombre']) ?></td>
    <td><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= htmlspecialchars($p['marca_nombre']) ?></td>
    <td>$<?= number_format($p['precio'],2) ?></td>
    <td><?= intval($p['cantidad']) ?></td>
    <td><?php if($p['imagen']): ?><img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" width="50"><?php endif;?></td>
    <td>
    <!-- Botón Toggle Edición JS -->
    <button onclick="toggleEdit(<?= $p['id'] ?>)" style="cursor:pointer; color:blue; background:none; border:none; text-decoration:underline;">Editar</button>
     - 
    <a href="?eliminar_pieza=<?= $p['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta pieza?')">Eliminar</a>
    </td>
    </tr>
    <!-- Fila oculta de edición -->
    <tr id="edit-row-<?= $p['id'] ?>" style="display:none; background-color: #f9f9f9;">
    <td colspan="8">
    <div class="form-edicion" style="padding: 20px; border: 1px solid #ddd;">
    <h4>Editar Pieza: <?= htmlspecialchars($p['nombre']) ?></h4>
    <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $p['id'] ?>">
    <input type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>" placeholder="Nombre" required>
    <textarea name="descripcion" placeholder="Descripción" rows="3" required><?= htmlspecialchars($p['descripcion']) ?></textarea>
    <input type="number" step="0.01" name="precio" value="<?= $p['precio'] ?>" placeholder="Precio" required>
    <input type="number" name="cantidad" value="<?= $p['cantidad'] ?>" placeholder="Cantidad" required>
    <select name="marca_id" required>
    <option value="">Selecciona marca</option>
    <?php 
    $marcas_edit = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
    while($m3 = $marcas_edit->fetch_assoc()): 
    ?>
    <option value="<?= $m3['id'] ?>" <?= $m3['id'] == $p['marca_id'] ? 'selected' : '' ?>>
    <?= htmlspecialchars($m3['nombre']) ?>
    </option>
    <?php endwhile; ?>
    </select>
    <label>Imagen Principal:</label>
    <input type="file" name="imagen">
    <small>Dejar vacío para mantener la imagen actual</small><br>

    <label>Agregar más imágenes a la galería:</label>
    <input type="file" name="galeria[]" multiple accept="image/*">

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

    <button type="submit" name="actualizar_pieza">Actualizar Pieza</button>
    <button type="button" onclick="toggleEdit(<?= $p['id'] ?>)" style="background-color: #6c757d;">Cancelar</button>
    </form>
    </div>
    </td>
    </tr>
    <?php endwhile; ?>
    </table>
    </section>
</main>

<footer style="text-align: center; margin-top: 20px; color: #888;">
&copy; <?= date('Y') ?> Mexican Racing Motor Parts
</footer>

<script>
// JS para mostrar/ocultar fila edición
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<?php endif; ?>
</body>
</html>