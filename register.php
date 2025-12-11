<?php
// Iniciamos o continuamos la sesión.
// Esto permite guardar datos temporales como mensajes o información del usuario.
session_start();

// Importamos el archivo donde está la conexión a la base de datos.
// require_once evita cargarlo dos veces.
require_once "conexion.php";

// Variables para mostrar mensajes al usuario.
$mensaje = "";
$exito = false;

// Lista de dominios permitidos para correos.
// Es una forma de evitar registros con correos sospechosos o poco confiables.
$dominios_validos = [
    'gmail.com', 'outlook.com', 'outlook.es',
    'hotmail.com', 'hotmail.es', 'yahoo.com',
    'yahoo.es', 'icloud.com'
];

// Si el usuario envió el formulario (POST), procesamos la información.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // trim elimina espacios innecesarios al inicio o al final.
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");

    // mb_strtolower convierte el texto a minúsculas respetando acentos y caracteres especiales.
    // Esto sirve para asegurar que el correo se compare correctamente sin importar cómo lo escribió el usuario.
    $correo = mb_strtolower($correo, 'UTF-8');

    // La contraseña se toma como viene; no se usa trim para no eliminar espacios que el usuario pueda querer.
    $contrasena = $_POST["contrasena"] ?? "";

    // Comprobamos que no haya campos vacíos.
    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "⚠️ Completa todos los campos.";
    }
    // Validamos que el correo tenga un formato correcto.
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no tiene un formato válido.";
    }
    else {

        // Separamos el correo en: nombre_usuario y dominio.
        // Si el correo es "juan@gmail.com", queda ["juan", "gmail.com"].
        $partes_correo = explode('@', $correo);
        $dominio = $partes_correo[1] ?? "";

        // Revisamos si el dominio del correo está en nuestra lista de permitidos.
        if (!in_array($dominio, $dominios_validos)) {

            // Tomamos algunos dominios para mostrarlos como ejemplo.
            $dominios_lista = implode(', ', array_slice($dominios_validos, 0, 5));

            $mensaje = "⚠️ Solo se permiten correos de dominios como: $dominios_lista, etc.";
        }
        // Requisito mínimo de longitud para la contraseña.
        elseif (strlen($contrasena) < 6) {
            $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        }
        else {

            // Encriptamos la contraseña antes de guardarla.
            // password_hash genera un hash seguro usando bcrypt (por defecto).
            // Esto es fundamental para no guardar contraseñas reales en la BD.
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Preparamos la consulta SQL para evitar inyecciones.
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($sql);

            // bind_param enlaza los valores a los ? de la consulta.
            // "sss" significa que los tres valores son strings.
            $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

            // Intentamos ejecutar la consulta.
            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
                $exito = true;

                // Redirección automática después de 2 segundos.
                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = 'inicio_secion.php';
                    }, 2000);
                </script>
                ";
            } else {
                // Si falla, puede que el correo ya exista porque es único.
                $mensaje = "⚠️ Error al registrar (posiblemente el correo ya existe).";
            }

            // Cerramos la consulta preparada.
            $stmt->close();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <!-- Título de la pestaña del navegador -->
    <title>Registro MRMP</title>

    <!-- Tipografía moderna desde Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <!-- Archivo CSS externo con el diseño -->
    <link rel="stylesheet" href="registro.css">

    <!-- Para que la web sea responsive en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- Formulario principal -->
<form method="post" class="formulario" novalidate>
    
    <!-- Logo y título -->
    <div class="logo-taller">
        <img src="img/mrmp-logo.png" alt="Logo MRMP">
        <h1>Registro MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <!-- Inputs -->
    <section class="seccion-informacion">

        <label>Nombre Completo</label>
        <!-- htmlspecialchars: evita ataques XSS al imprimir datos -->
        <input type="text" name="nombre"
               placeholder="Ej: Jesus Mendez"
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
               required>

        <label>Correo Electrónico</label>
        <input type="email" name="correo"
               placeholder="ejemplo@gmail.com"
               value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
               required>

        <label>Contraseña</label>
        <input type="password" name="contrasena"
               placeholder="Mínimo 6 caracteres"
               required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Crear Cuenta</button>
        <p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
    </section>
</form>

<!-- MENSAJE MODAL DE ÉXITO O ERROR -->
<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">

        <!-- Título del modal -->
        <h2><?= $exito ? "🔧 Registro Completado" : "❌ Error" ?></h2>

        <!-- Mensaje dinámico -->
        <p><?= htmlspecialchars($mensaje) ?></p>

        <!-- Si hubo éxito muestra aviso de redirección -->
        <?php if($exito): ?>
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>

        <!-- Si hubo error, botón para cerrar modal -->
        <?php else: ?>
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPT PARA CERRAR MODAL -->
<script>
// Esta función oculta el modal cuando se hace clic en "Cerrar"
function cerrarModal() { 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>

<?php endif; ?>

</body>
</html>
