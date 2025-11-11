<?php
session_start();
require_once "conexion.php";

$claveCreador = "CBTIS52";
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: admin_panel.php");
    exit;
}

// Crear nuevo admin
if(isset($_POST['crear_admin'])){
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $clave = $_POST['clave_creador'];

    if($clave !== $claveCreador){
        $_SESSION['mensaje'] = "❌ Clave del creador incorrecta.";
    } elseif($nombre && $correo && $contrasena){
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO admins(nombre, correo, contrasena_hash) VALUES(?,?,?)");
        $stmt->bind_param("sss",$nombre,$correo,$hash);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "✅ Admin creado correctamente.";
    }
    header("Location: admin_panel.php");
    exit;
}

// Login admin
if(isset($_POST['login_admin'])){
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];

    $stmt = $conexion->prepare("SELECT * FROM admins WHERE correo=?");
    $stmt->bind_param("s",$correo);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res && $res->num_rows==1){
        $admin = $res->fetch_assoc();
        if(password_verify($contrasena, $admin['contrasena_hash'])){
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            header("Location: admin_panel.php");
            exit;
        }
    }
    $_SESSION['mensaje'] = "❌ Correo o contraseña incorrectos.";
    $stmt->close();
    header("Location: admin_panel.php");
    exit;
}

// Si admin logueado -> panel
if(isset($_SESSION['admin_id'])){

    // Agregar marca
    if(isset($_POST['nueva_marca'])){
        $nombre_marca = trim($_POST['nombre_marca']);
        if($nombre_marca !== ''){
            $stmt = $conexion->prepare("INSERT INTO marcas(nombre) VALUES(?)");
            $stmt->bind_param("s",$nombre_marca);
            $stmt->execute();
            $stmt->close();
            $_SESSION['mensaje'] = "✅ Marca agregada correctamente.";
            header("Location: admin_panel.php");
            exit;
        }
    }

    // Eliminar marca
    if(isset($_GET['eliminar_marca'])){
        $id = intval($_GET['eliminar_marca']);
        $conexion->query("DELETE FROM marcas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Marca eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar TODAS las piezas
    if(isset($_POST['eliminar_todas_piezas'])){
        $conexion->query("DELETE FROM piezas");
        $_SESSION['mensaje'] = "✅ Todas las piezas han sido eliminadas.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar TODAS las marcas
    if(isset($_POST['eliminar_todas_marcas'])){
        $conexion->query("DELETE FROM marcas");
        $_SESSION['mensaje'] = "✅ Todas las marcas han sido eliminadas.";
        header("Location: admin_panel.php");
        exit;
    }

    // Agregar pieza
    if(isset($_POST['agregar_pieza'])){
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $cantidad = intval($_POST['cantidad']);
        $marca_id = intval($_POST['marca_id']);
        $imagen = '';

        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
        }

        $stmt = $conexion->prepare("INSERT INTO piezas(nombre, descripcion, precio, cantidad, marca_id, imagen) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssdiis",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen);
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "✅ Pieza agregada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

 // Eliminar usuario 
    if(isset($_GET['eliminar_pieza'])){
        $id = intval($_GET['eliminar_pieza']);
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

    // Actualizar pieza
    if(isset($_POST['actualizar_pieza'])){
        $id = intval($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $cantidad = intval($_POST['cantidad']);
        $marca_id = intval($_POST['marca_id']);

        // Si se sube nueva imagen
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=?, imagen=? WHERE id=?");
            $stmt->bind_param("ssdiisi",$nombre,$descripcion,$precio,$cantidad,$marca_id,$imagen,$id);
        } else {
            $stmt = $conexion->prepare("UPDATE piezas SET nombre=?, descripcion=?, precio=?, cantidad=?, marca_id=? WHERE id=?");
            $stmt->bind_param("ssdiii",$nombre,$descripcion,$precio,$cantidad,$marca_id,$id);
        }
        
        $stmt->execute();
        $stmt->close();
        $_SESSION['mensaje'] = "✅ Pieza actualizada correctamente.";
        header("Location: admin_panel.php");
        exit;
    }

    // Eliminar pieza
    if(isset($_GET['eliminar_pieza'])){
        $id = intval($_GET['eliminar_pieza']);
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        header("Location: admin_panel.php");
        exit;
    }

    // Listar marcas y piezas
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
<link rel="stylesheet" href="admin.css">
</head>
<body>

<?php if(!isset($_SESSION['admin_id'])): ?>
<!-- Login / Crear Admin -->
<main>
<?php if($mensaje): ?>
<div class="modal-mensaje exito">
<div class="modal-contenido">
<h2>Mensaje</h2>
<p><?= htmlspecialchars($mensaje) ?></p>
<button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
</div>
</div>
<?php endif; ?>

<section class="formulario">
<h2>Login Admin</h2>
<form method="post">
<input type="email" name="correo" placeholder="Correo" required>
<input type="password" name="contrasena" placeholder="Contraseña" required>
<button type="submit" name="login_admin">Iniciar Sesión</button>
</form>
</section>

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

<?php else: ?>
<!-- Panel Admin -->
<header>
<h1>Panel de Administración MRMP</h1>
<a href="?logout" style="color:#ff0000;">Cerrar sesión</a>
</header>

<main>
<?php if($mensaje): ?>
<div class="modal-mensaje exito">
<div class="modal-contenido">
<h2>Mensaje</h2>
<p><?= htmlspecialchars($mensaje) ?></p>
<button onclick="this.parentElement.parentElement.style.display='none'">Cerrar</button>
</div>
</div>
<?php endif; ?>


<section class="formulario">
<h2>Gestionar Marcas</h2>
<form method="post">
<input type="text" name="nombre_marca" placeholder="Nombre de la marca" required>
<button type="submit" name="nueva_marca">Agregar Marca</button>
</form>

<div class="acciones-rapidas">
<form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las marcas?')">
<button type="submit" name="eliminar_todas_marcas" class="eliminar">Eliminar Todas las Marcas</button>
</form>
</div>

<h3>Marcas Registradas</h3>
<ul>
<?php while($m = $marcas->fetch_assoc()): ?>
<li><?= htmlspecialchars($m['nombre']) ?> - <a href="?eliminar_marca=<?= $m['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta marca?')">Eliminar</a></li>
<?php endwhile; ?>
</ul>
</section>

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
<input type="file" name="imagen">
<button type="submit" name="agregar_pieza">Agregar Pieza</button>
</form>

<div class="acciones-rapidas">
<form method="post" onsubmit="return confirm('¿Estás seguro de eliminar TODAS las piezas?')">
<button type="submit" name="eliminar_todas_piezas" class="eliminar">Eliminar Todas las Piezas</button>
</form>
</div>

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
<a href="?eliminar_pieza=<?= $p['id'] ?>" style="color:red;" onclick="return confirm('¿Eliminar esta pieza?')">Eliminar</a>
</td>
</tr>
<!-- Formulario de edición para cada pieza -->
<tr>
<td colspan="8">
<div class="form-edicion">
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
<input type="file" name="imagen">
<small>Dejar vacío para mantener la imagen actual</small><br>
<button type="submit" name="actualizar_pieza">Actualizar Pieza</button>
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

<?php endif; ?>
</body>
</html>