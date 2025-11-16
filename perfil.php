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
<title>Perfil MRMC</title>
<link rel="stylesheet" href="perfil.css">
</head>
<body>

<header>
  <div class="logo">
    <img src="img/mrmp logo.png" alt="mrmp logo">
    <p>Mexican Racing Motor Parts</p>
  </div>
  <div class="usuario">
      <span class="saludo">Hola, <?= htmlspecialchars($usuario['nombre']) ?></span>
      <a href="dashboard-piezas.php">Dashboard</a>
      <a href="carrito.php">Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a>
      <a href="logout.php" class="btn-logout">Cerrar sesión</a>
      <a href="blog.php">Blog</a>
  </div>
</header>

<main>
<h2>Perfil de <?= htmlspecialchars($usuario['nombre'] ?? 'Usuario') ?></h2>

<?php if($mensaje): ?>
<div class="modal-mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="seccion">
    <img src="uploads/<?= htmlspecialchars($usuario['imagen_perfil'] ?? 'default.png') ?>" alt="Imagen de perfil">
    <form method="post" enctype="multipart/form-data">
        <label>Actualizar imagen de perfil:</label>
        <input type="file" name="imagen" accept="image/*" required>
        <button type="submit">Subir Imagen</button>
    </form>
</div>

<div class="seccion">
    <h3>Información Personal</h3>
    <p><strong>Nombre:</strong> <?= htmlspecialchars($usuario['nombre'] ?? '-') ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($usuario['correo'] ?? '-') ?></p>
    <p><strong>Teléfono:</strong> <?= htmlspecialchars($usuario['telefono'] ?? 'No agregado') ?></p>
    <p><strong>Verificado:</strong> 
        <span class="<?= $usuario['verificado'] ? 'verificado' : 'no-verificado' ?>">
            <?= $usuario['verificado'] ? 'Sí ✅' : 'No ❌' ?>
        </span>
    </p>
    <p><strong>Cuenta creada:</strong> <?= htmlspecialchars($usuario['fecha_creacion'] ?? '-') ?></p>
</div>

<div class="seccion">
    <h3>Actualizar Correo Electrónico</h3>
    <form method="post">
        <input type="email" name="nuevo_correo" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>" placeholder="Nuevo correo" required>
        <input type="password" name="contrasena_actual" placeholder="Contraseña actual" required>
        <button type="submit" name="actualizar_correo">Actualizar Correo</button>
    </form>
</div>

<div class="seccion">
    <h3>Gestionar Teléfono</h3>
    <form method="post">
        <input type="tel" name="telefono" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="Ej: +1234567890" pattern="[0-9\s\-\+\(\)]{8,20}">
        <button type="submit" name="actualizar_telefono"><?= $usuario['telefono'] ? 'Actualizar Teléfono' : 'Agregar Teléfono' ?></button>
        <?php if($usuario['telefono']): ?>
        <button type="submit" name="actualizar_telefono" onclick="document.querySelector('input[name=\'telefono\']').value = '';">Eliminar Teléfono</button>
        <?php endif; ?>
    </form>
</div>

<div class="seccion">
    <h3>Cambiar Contraseña</h3>
    <p><a href="recuperar.php">Cambiar contraseña en este link</a></p>

</div>
<footer>
  <p>© <?= date('Y') ?> <span>Mexican Racing Motor Parts</span></p>
</footer>

</main>
</body>
</html>
