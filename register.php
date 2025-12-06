<?php
// session_start: Inicia sesión para manejar variables de sesión necesarias
session_start();

// require_once: Carga el archivo de conexión a la base de datos
require_once "conexion.php";

$mensaje = ""; // Mensaje para feedback al usuario
$exito = false; // Estado del registro

// Lista blanca de dominios de correo permitidos para registro
$dominios_validos = [
    'gmail.com', 'outlook.com', 'outlook.es', 
    'hotmail.com', 'hotmail.es', 'yahoo.com', 
    'yahoo.es', 'icloud.com'
];

// Procesamiento del Formulario: Solo si es POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitización básica: trim elimina espacios en blanco inicio/fin
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    // mb_strtolower: Normaliza el correo a minúsculas para comparaciones consistentes
    $correo = mb_strtolower($correo, 'UTF-8'); 
    $contrasena = $_POST["contrasena"] ?? "";

    // Validación 1: Campos vacíos
    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "⚠️ Completa todos los campos.";
    } 
    // Validación 2: Formato de correo electrónico
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no tiene un formato válido.";
    } 
    else {
        // Validación 3: Dominio del correo
        // explode: Separa el correo en partes usando '@' como delimitador
        $partes_correo = explode('@', $correo);
        // Obtiene la parte del dominio (índice 1)
        $dominio = isset($partes_correo[1]) ? $partes_correo[1] : '';
        
        // in_array: Verifica si el dominio extraído existe en la lista de permitidos
        if (!in_array($dominio, $dominios_validos)) {
            // Genera string de ejemplos para el mensaje de error
            $dominios_lista = implode(', ', array_slice($dominios_validos, 0, 5));
            $mensaje = "⚠️ Solo se permiten correos de dominios verificados como: " . $dominios_lista . ", etc.";
        } 
        // Validación 4: Longitud mínima de contraseña
        elseif (strlen($contrasena) < 6) {
            $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        } 
        else {
            // Hashing: Encripta la contraseña usando algoritmo seguro por defecto (bcrypt)
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Inserción en BD: Prepara SQL para insertar nuevo usuario
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            // bind_param: Asigna valores (s=string, s=string, s=string)
            $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

            // Execute: Intenta realizar la inserción
            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
                $exito = true;
                
                // Redirección automática al login tras 2 segundos
                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = 'inicio_secion.php';
                    }, 2000);
                </script>
                ";
            } else {
                // Error en inserción (probablemente correo duplicado por restricción UNIQUE en BD)
                $mensaje = " ⚠️ Error al registrar (posiblemente el correo ya existe).";
            }
        // Cierra statement
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
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- Reutiliza CSS de inicio de sesión para consistencia visual -->
    <link rel="stylesheet" href="inicio_secion.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- Formulario de Registro -->
<form method="post" class="formulario" novalidate>
    <div class="logo-taller">
        <img src="img/mrmp-logo.png" alt="Logo MRMP">
        <h1>Registro MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <section class="seccion-informacion">
        <label>Nombre Completo</label>
        <!-- Input Nombre: Mantiene valor si hay error -->
        <input type="text" name="nombre" placeholder="Ej: Jesus Mendez" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>

        <label>Correo Electrónico</label>
        <!-- Input Correo -->
        <input type="email" name="correo" placeholder="ejemplo@gmail.com" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <!-- Input Password -->
        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Crear Cuenta</button>
        <!-- Enlace para volver al login -->
        <p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
    </section>
</form>

<!-- Modal de Feedback (Éxito o Error) -->
<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <!-- Título condicional -->
        <h2><?= $exito ? "🔧 Registro Completado" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        
        <?php if($exito): ?>
            <!-- Mensaje de redirección -->
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>
        <?php else: ?>
            <!-- Botón cerrar modal -->
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>
<!-- Script para manejo del modal -->
<script>
function cerrarModal() { 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>
<?php endif; ?>

</body>
</html>