<?php
// session_start: Inicia sesión para poder usar variables de sesión (como $_SESSION['usuario_id'])
session_start();

// require_once: Incluye el archivo de conexión a la base de datos de forma obligatoria
require_once "conexion.php";

$mensaje = ""; // Inicializa variable para mensajes de error o éxito
$exito = false; // Bandera booleana para controlar el estado del login (true si es exitoso)

// Verificar Request Method: Comprueba si el formulario fue enviado vía POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtener datos del form: Usa el operador null coalesce (??) para evitar errores si no existen
    $correo = trim($_POST["correo"] ?? ""); // trim elimina espacios al inicio y final
    $contrasena = $_POST["contrasena"] ?? "";

    // Validación Básica: Verifica que los campos no estén vacíos
    if ($correo === "" || $contrasena === "") {
        $mensaje = "Ingresa tu correo y contraseña.";
    } 
    // Validación de Formato: filter_var comprueba si el string es un email válido
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "Correo inválido.";
    } else {
        // Consulta SQL: Busca el usuario por su correo electrónico
        $sql = "SELECT id, nombre, correo, contrasena_hash FROM usuarios WHERE correo = ?";
        
        // prepare: Prepara la sentencia SQL para evitar inyección SQL
        $stmt = $conexion->prepare($sql);
        // bind_param: Vincula la variable $correo al parámetro ? (s = string)
        $stmt->bind_param("s", $correo);
        // execute: Ejecuta la consulta preparada
        $stmt->execute();
        // get_result: Obtiene el conjunto de resultados de la base de datos
        $resultado = $stmt->get_result();

        // Verificación de existencia: Si num_rows es 1, el usuario existe
        if ($resultado && $resultado->num_rows === 1) {
            // fetch_assoc: Obtiene la fila de datos como un array asociativo
            $usuario = $resultado->fetch_assoc();
            
            // password_verify: Compara la contraseña ingresada con el hash almacenado
            if (password_verify($contrasena, $usuario["contrasena_hash"])) {
                // Login Exitoso: Guarda los datos críticos del usuario en la sesión
                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["usuario_nombre"] = $usuario["nombre"];
                $_SESSION["usuario_correo"] = $usuario["correo"];
                
                // Configura mensaje de bienvenida y bandera de éxito
                $mensaje = "¡Bienvenido a MRMP, " . $usuario["nombre"] . "!";
                $exito = true;
                
                // Redirección JS: Usa JavaScript para redirigir tras 2 segundos (para leer el mensaje)
                echo "
                <script>
                    setTimeout(function() {
                        window.location.href = 'pagina-principal.php';
                    }, 2000); 
                </script>
                ";
            } else {
                // Contraseña incorrecta
                $mensaje = " ⚠️Correo o contraseña incorrectos.";
            }
        } else {
            // Correo no encontrado en la base de datos
            $mensaje = " ⚠️Correo no encontrado.";
        }
        // Cerrar statement: Libera recursos del statement
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Metadatos básicos -->
    <meta charset="UTF-8">
    <title>Login MRMP</title>
    <!-- Google Fonts: Carga la fuente Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- CSS: Enlace a la hoja de estilos específica para inicio de sesión -->
    <link rel="stylesheet" href="inicio_secion.css">
    <!-- Viewport: Ajuste necesario para la responsividad en móviles -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- Formulario de Inicio de Sesión -->
<!-- method="post": Envía los datos de forma segura en el cuerpo de la petición -->
<form method="post" class="formulario" novalidate>
    <!-- Encabezado del formulario con Logo -->
    <div class="logo-taller">
        <img src="img/mrmp-logo.png" alt="Logo MRMP">
        <h1>Inicio de sesión MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <!-- Sección de Campos de Entrada -->
    <section class="seccion-informacion">
        <label>Correo</label>
        <!-- value preservado: Mantiene el correo escrito si hay un error -->
        <input type="email" name="correo" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena" required minlength="6">
    </section>

    <!-- Botones y Enlaces -->
    <section class="seccion-botones">
        <!-- Botón de envío -->
        <button type="submit">Iniciar sesión</button>
        
        <!-- Enlace a Registro -->
        <p>¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
        
        <!-- Enlace a Recuperación de Contraseña -->
        <p>¿Olvidaste tu contraseña?</p>
        <a href="recuperar.php">Recuperar Tu Contraseña</a>
        
        <!-- Acceso al Panel de Administración -->
        <div class="panel-admin">
            <p>Solo personal Autorizado</p>
            <a href="admin_panel.php">Admin Panel</a>
        </div>
    </section>
</form>

<!-- Modal de Mensaje: Se muestra solo si $mensaje no está vacío -->
<?php if($mensaje): ?>
<!-- Clase condicional: Añade 'exito' o 'error' según el estado de $exito -->
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <!-- Título dinámico del modal -->
        <h2><?= $exito ? "🔧 Bienvenido al Taller MRMP! " : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        
        <!-- Contenido condicional del pie del modal -->
        <?php if($exito): ?>
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>
        <?php else: ?>
            <!-- Botón para cerrar el modal manualmente si es un error -->
            <button onclick="cerrarmodal()">Cerrar Modal</button>
        <?php endif; ?>
    </div>
</div>

<script>
    // Función JS para ocultar el modal al hacer click en Cerrar
    function cerrarmodal() {
        document.querySelector('.modal-mensaje').style.display='none';
    }
</script>
<?php endif; ?>

</body>
</html>
