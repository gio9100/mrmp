<?php
session_start();
require_once "conexion.php";

$mensaje = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($correo === "" || $contrasena === "") {
        $mensaje = "Ingresa tu correo y contraseña.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido.";
    } else {
        $sql = "SELECT id, nombre, correo, contrasena_hash FROM usuarios WHERE correo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($contrasena, $usuario["contrasena_hash"])) {
                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nombre"] = $usuario["nombre"];
                $_SESSION["usuario_correo"] = $usuario["correo"];
                $mensaje = "¡Bienvenido a MRMC, " . $usuario["nombre"] . "!";
                $exito = true;
            } else {
                $mensaje = "Correo o contraseña incorrectos.";
            }
        } else {
            $mensaje = "Correo o contraseña incorrectos.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Login MRMP</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="inicio_secion.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<form method="post" class="formulario" novalidate>
    <div class="logo-taller">
        <img src="images/mrmp logo.png" alt="Logo MRMC">
        <h1>Inicio de sesión MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <section class="seccion-informacion">
        <label>Correo</label>
        <input type="email" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena" required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Iniciar sesión</button>
        <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
        <p>¿Olvidaste tu contraseña?</p>
    </section>
</form>

<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <h2><?= $exito ? "🔧 Bienvenido al Taller MRMC" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        <?php if($exito): ?>
            <button onclick="window.location.href='dashboard-piezas.php'">Ir al Panel</button>
        <?php else: ?>
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>
<script>
function cerrarModal(){ 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>
<?php endif; ?>

</body>
</html>
