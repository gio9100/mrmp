<?php
// session_start(): Inicia una sesión. 
// Sirve para persistir datos del usuario (como ID, Nombre) a través de las distintas páginas del sitio.
session_start();

// require_once: Incluye el archivo de conexión.
// Sirve para cargar la configuración de la base de datos necesaria para ejecutar consultas.
require_once "conexion.php";

$mensaje = ""; // Variable para almacenar mensajes de error o éxito.
$exito = false; // Bandera para indicar si el inicio de sesión fue correcto.

// $_SERVER["REQUEST_METHOD"]: Contiene el método de solicitud (GET, POST, etc.).
// Sirve para verificar si el usuario envió el formularo (POST).
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // trim(): Elimina espacios en blanco al inicio y final.
    // Sirve para limpiar la entrada del usuario y evitar errores por espacios accidentales.
    // ?? "": Operador de fusión de null. Si $_POST["correo"] no existe, asigna una cadena vacía.
    $correo = trim($_POST["correo"] ?? ""); 
    $contrasena = $_POST["contrasena"] ?? "";

    // Validación: Verificar si los campos están vacíos.
    if ($correo === "" || $contrasena === "") {
        $mensaje = "Ingresa tu correo y contraseña.";
    } 
    // filter_var(..., FILTER_VALIDATE_EMAIL): Valida si un string es un email correcto.
    // Sirve para asegurar que el formato del correo sea válido antes de consultar la BD.
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido.";
    } else {
        // Consulta SQL para obtener los datos del usuario.
        // Se seleccionan ID, nombre, correo y el HASH de la contraseña.
        $sql = "SELECT id, nombre, correo, contrasena_hash FROM usuarios WHERE correo = ?";
        
        // $conexion->prepare(): Prepara la consulta SQL en el servidor.
        // Sirve para mejorar la seguridad y eficiencia (Previene Inyección SQL).
        $stmt = $conexion->prepare($sql);
        
        // bind_param("s", ...): Vincula la variable $correo al marcador "?" de la consulta.
        // "s" indica que el dato es un string.
        $stmt->bind_param("s", $correo);
        
        // execute(): Ejecuta la consulta preparada.
        $stmt->execute();
        
        // get_result(): Obtiene el conjunto de resultados.
        $resultado = $stmt->get_result();

        // Validar si se encontró un usuario.
        // num_rows: Cuenta cuántas filas devolvió la consulta. Sirve para saber si el correo existe.
        if ($resultado && $resultado->num_rows === 1) {
            // fetch_assoc(): Obtiene la fila actual como un array asociativo.
            $usuario = $resultado->fetch_assoc();
            
            // password_verify(): Compara la contraseña ingresada con el hash almacenado.
            // Sirve para verificar la contraseña de forma segura (sin guardarla en texto plano).
            if (password_verify($contrasena, $usuario["contrasena_hash"])) {
                // Login Correcto: Guardar datos en variables de sesión.
                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nombre"] = $usuario["nombre"];
                $_SESSION["usuario_correo"] = $usuario["correo"];
                
                $mensaje = "¡Bienvenido a MRMP, " . $usuario["nombre"] . "!";
                $exito = true;
                
                // JS para redireccionar.
                // setTimeout(): Ejecuta una función después de un tiempo (2000ms = 2s).
                // Sirve para que el usuario pueda leer el mensaje de bienvenida antes de ir a la home.
                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = 'pagina-principal.php';
                    }, 2000); 
                </script>
                ";
            } else {
                $mensaje = " ⚠️Correo o contraseña incorrectos.";
            }
        } else {
            $mensaje = " ⚠️Correo no encontrado.";
        }
        // $stmt->close(): Cierra la sentencia preparada.
        // Sirve para liberar los recursos asociados a la consulta.
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login MRMP</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="inicio_secion.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- <form>: Elemento para capturar datos. -->
<!-- method="post": Método HTTP para enviar datos sensibles (no se ven en la URL). -->
<!-- novalidate: Desactiva la validación por defecto del navegador para usar la nuestra. -->
<form method="post" class="formulario" novalidate>
    <div class="logo-taller">
        <img src="img/mrmp-logo.png" alt="Logo MRMP">
        <h1>Inicio de sesión MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <section class="seccion-informacion">
        <label>Correo</label>
        <!-- value="<?= ... ?>": Mantiene el valor ingresado si hay error. -->
        <!-- htmlspecialchars(): Previene inyección de código HTML/JS (XSS) al mostrar el valor. -->
        <input type="email" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena" required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Iniciar sesión</button>
        
        <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
        
        <p>¿Olvidaste tu contraseña?</p>
        <a href="recuperar.php">Recuperar Tu Contraseña</a>
        
        <div class="panel-admin">
            <p>Solo personal Autorizado</p>
            <a href="admin_panel.php">Admin Panel</a>
        </div>
    </section>
</form>

<?php if($mensaje): ?>
<!-- Operador ternario para clase CSS (exito/error) -->
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <h2><?= $exito ? "🔧 Bienvenido al Taller MRMP! " : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        
        <?php if($exito): ?>
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>
        <?php else: ?>
            <!-- onclick="cerrarmodal()": Ejecuta la función JS al hacer clic. -->
            <button onclick="cerrarmodal()">Cerrar Modal</button>
        <?php endif; ?>
    </div>
</div>

<script>
    // Función para ocultar el modal cambiando su estilo CSS display.
    function cerrarmodal() {
        document.querySelector('.modal-mensaje').style.display='none';
    }
</script>
<?php endif; ?>

</body>
</html>
