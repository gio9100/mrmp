<?php
session_start();
require_once "conexion.php";

$mensaje = "";
$exito = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "Completa todos los campos.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo no es válido.";
    } elseif (strlen($contrasena) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

        // Código corregido - sin el campo rol
        $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

        if ($stmt->execute()) {
            $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
            $exito = true;
        } else {
            $mensaje = ($conexion->errno===1062) ? "⚠️ El correo ya está registrado." : "❌ Error: ".$conexion->error;
        }
        $stmt->close();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y normalizar
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $correo = mb_strtolower($correo, 'UTF-8');

    // Validación básica con filter_var
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die('Correo inválido.');
    }

    // Validación que sea exactamente dominio gmail.com
    if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $correo)) {
        die('Solo se permiten correos @gmail.com');
    }

    // Si llega aquí, el correo tiene formato válido y es @gmail.com
    // Aquí puedes continuar (guardar en DB, enviar email, etc.)
    echo 'Correo aceptado: ' . htmlspecialchars($correo);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro MRMC</title>
<link rel="stylesheet" href="registro.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<form action="register.php" method="post" novalidate>
<form method="post" class="formulario" novalidate>
<section class="seccion-informacion">
<h2>Registro MRMC</h2>
<label>Nombre</label>
<input type="text" name="nombre" required>
<label>Correo</label>
<label>Correo</label>
<input type="email" id="correo" name="correo" required>
<div id="mensaje-correo" class="mensaje-correo">⚠️ Solo se aceptan correos @gmail.com</div>
<label>Contraseña</label>
<input type="password" name="contrasena" required minlength="6">
</section>
<section class="seccion-botones">
<button type="submit">Crear cuenta</button>
<p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
</section>
</form>

<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <h2><?= $exito ? "🚗 ¡Bienvenido a MRMC!" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        <?php if($exito): ?>
            <button onclick="window.location.href='inicio_secion.php'">Ir al Login</button>
        <?php else: ?>
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>
<script>
const correoInput = document.getElementById('correo');
const mensajeCorreo = document.getElementById('mensaje-correo');
correoInput.addEventListener('input', () => {
  const val = correoInput.value.trim().toLowerCase();
  const re = /^[a-z0-9._%+-]+@gmail\.com$/;
  if (val && !re.test(val)) {
    correoInput.classList.add('error');
    mensajeCorreo.style.display = 'block';
  } else {
    correoInput.classList.remove('error');
    mensajeCorreo.style.display = 'none';
  }
});
</script>
<script>
function cerrarModal(){ document.querySelector('.modal-mensaje').style.display='none'; }
</script>
<?php endif; ?>
</body>
</html>