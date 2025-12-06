<?php
// session_start(): Inicia la sesión.
// Sirve para persistir datos como mensajes de error o datos de usuario entre páginas.
session_start();

// require_once: Se asegura de que el archivo 'conexion.php' se incluya una sola vez.
// Sirve para establecer la conexión con la base de datos MySQL.
require_once "conexion.php";

$mensaje = "";
$exito = false;

// Array de dominios permitidos.
// Sirve para restringir el registro solo a correos con estos dominios específicos.
$dominios_validos = [
    'gmail.com', 'outlook.com', 'outlook.es', 
    'hotmail.com', 'hotmail.es', 'yahoo.com', 
    'yahoo.es', 'icloud.com'
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // trim(): Elimina espacios en blanco.
    // Sirve para limpiar los inputs.
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    
    // mb_strtolower(): Convierte el string a minúsculas usando codificación multibyte.
    // Sirve para normalizar el correo (GMAIL.COM es igual a gmail.com).
    $correo = mb_strtolower($correo, 'UTF-8'); 
    $contrasena = $_POST["contrasena"] ?? "";

    // Validaciones básicas.
    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "⚠️ Completa todos los campos.";
    } 
    // filter_var(): Valida el email.
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no tiene un formato válido.";
    } 
    else {
        // explode('@', $correo): Divide el string en un array usando '@' como delimitador.
        // Sirve para separar usuario y dominio (ej. ['usuario', 'gmail.com']).
        $partes_correo = explode('@', $correo);
        $dominio = isset($partes_correo[1]) ? $partes_correo[1] : '';
        
        // in_array(): Verifica si un valor existe en un array.
        // Sirve para comprobar si el dominio extraído está en la lista blanca $dominios_validos.
        if (!in_array($dominio, $dominios_validos)) {
            // implode(): Une elementos de un array en un string.
            // Sirve para mostrar una lista legible de dominios permitidos al usuario.
            $dominios_lista = implode(', ', array_slice($dominios_validos, 0, 5));
            $mensaje = "⚠️ Solo se permiten correos de dominios verificados como: " . $dominios_lista . ", etc.";
        } 
        // strlen(): Obtiene la longitud de un string.
        // Sirve para imponer una longitud mínima de seguridad a la contraseña.
        elseif (strlen($contrasena) < 6) {
            $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        } 
        else {
            // password_hash(): Crea un hash seguro de la contraseña.
            // Sirve para no guardar contraseñas en texto plano en la BD (Seguridad Fundamental).
            // PASSWORD_DEFAULT usa el algoritmo bcrypt actual.
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Inserción en Base de Datos.
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            // "sss" indica que pasamos 3 strings como parámetros.
            $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

            // Si execute devuelve true, la inserción fue exitosa.
            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
                $exito = true;
                
                // Redirigir al usuario al login tras 2 segundos.
                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = 'inicio_secion.php';
                    }, 2000);
                </script>
                ";
            } else {
                $mensaje = " ⚠️ Error al registrar (posiblemente el correo ya existe).";
            }
        $stmt->close();
    }
}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro MRMP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Reutilizamos CSS de inicio de sesión para mantener la consistencia visual. -->
    <link rel="stylesheet" href="inicio_secion.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<form method="post" class="formulario" novalidate>
    <div class="logo-taller">
        <img src="img/mrmp-logo.png" alt="Logo MRMP">
        <h1>Registro MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <section class="seccion-informacion">
        <label>Nombre Completo</label>
        <input type="text" name="nombre" placeholder="Ej: Jesus Mendez" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>

        <label>Correo Electrónico</label>
        <input type="email" name="correo" placeholder="ejemplo@gmail.com" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Crear Cuenta</button>
        <p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
    </section>
</form>

<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <h2><?= $exito ? "🔧 Registro Completado" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        
        <?php if($exito): ?>
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>
        <?php else: ?>
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>
<script>
// Función para cerrar el modal ocultando su contenedor.
function cerrarModal() { 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>
<?php endif; ?>

</body>
</html>