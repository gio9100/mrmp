<?php
// Inicia la sesión del usuario para poder mantener variables entre páginas.
session_start();

// Configuración de la conexión a la base de datos.
// Definimos el host, nombre de la BD, usuario y contraseña.
$host = 'localhost';
$dbname = 'bootcamp2';
$username = 'root';
$password = '';

// Bloque try-catch para manejar errores de conexión de forma controlada.
try {
    // Creamos una nueva instancia de PDO para conectar a MySQL.
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Configuramos PDO para que lance excepciones en caso de error.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Si falla la conexión, mostramos el mensaje de error y detenemos el script.
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Incluimos las librerías de PHPMailer necesarias para enviar correos.
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Importamos las clases de PHPMailer al espacio de nombres global.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Variables para almacenar mensajes de retroalimentación para el usuario.
$mensaje = "";
$tipo_mensaje = "";

// 1. LÓGICA DE SOLICITUD DE RECUPERACIÓN (Paso 1)
// Verificamos si el usuario envió el formulario con su correo (y no está enviando la nueva contraseña aún).
if (isset($_POST['correo']) && !isset($_POST['nueva_password'])) {

    // Limpiamos espacios en blanco al inicio y final del correo ingresado.
    $correo = trim($_POST['correo']);

    // Preparamos una consulta SQL para buscar al usuario por su correo.
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE correo = ?");
    // Ejecutamos la consulta pasando el correo.
    $stmt->execute([$correo]);
    // Obtenemos el resultado (si existe).
    $usuario = $stmt->fetch();

    // Si encontramos un usuario con ese correo...
    if ($usuario) {

        // Generamos un token criptográficamente seguro de 32 bytes y lo convertimos a hexadecimal.
        // Sirve para identificar esta solicitud de recuperación de forma única.
        $token = bin2hex(random_bytes(32));

        // Establecemos que el token expira en 1 hora a partir de ahora.
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardamos el token y su fecha de expiración en la base de datos para este usuario.
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira = ? WHERE correo = ?");
        // Ejecutamos la actualización. Si tiene éxito, procedemos a enviar el correo.
        if ($stmt->execute([$token, $expiracion, $correo])) {

            // Construimos el enlace de recuperación que se enviará por correo.
            $enlace = "http://localhost/mrmp/recuperar.php?token=$token";

            // Creamos una nueva instancia de PHPMailer habilitando las excepciones.
            $mail = new PHPMailer(true);

            try {
                // Configuramos el servidor para usar SMTP.
                $mail->isSMTP();
                // Servidor SMTP de Gmail.
                $mail->Host = 'smtp.gmail.com'; 
                // Activamos la autenticación SMTP.
                $mail->SMTPAuth = true; 
                // Correo electrónico desde el que se enviará.
                $mail->Username = 'mexicanracermp@gmail.com'; 
                // Contraseña de aplicación (no la contraseña normal) para mayor seguridad.
                $mail->Password = 'ynxm gxio tuku ssba';  
                // Usamos encriptación TLS para proteger la comunicación.
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                // Puerto TCP para conectar (587 es estándar para TLS).
                $mail->Port = 587; 

                // Configuramos quién envía el correo.
                $mail->setFrom('mexicanracermp@gmail.com', 'Restablecer password');
                // Agregamos al destinatario (el usuario que solicitó recuperar).
                $mail->addAddress($correo, $usuario['nombre']);

                // Habilitamos el formato HTML para el cuerpo del correo.
                $mail->isHTML(true);
                // Definimos el asunto del correo.
                $mail->Subject = "Restablecer password MRMP";
                // Incrustamos una imagen (logo) en el cuerpo del correo para que se vea profesional.
                $mail->addEmbeddedImage('img/mrmp-logo.png', 'logoLab');

                // Cuerpo del mensaje en HTML.
                $mail->Body = "
                    <center>
                        <img src='cid:logoLab' width='150' style='margin-bottom:20px;'>
                    </center>

                    <h2>Recuperación de contraseña</h2>
                    Hola <strong>{$usuario['nombre']}</strong>,<br><br>

                    Has solicitado recuperar tu contraseña.<br><br>

                    <a href='$enlace' 
                       style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;'>
                       Restablecer contraseña
                    </a>

                    <br><br>
                    Si el botón no funciona abre este enlace:<br>
                    $enlace
                    <br><br>
                    Este enlace expira en 1 hora.<br>
                    <strong>Si tú no solicitaste el cambio de contraseña, ignora este correo.</strong>
                ";

                // Cuerpo alternativo en texto plano para clientes de correo que no soportan HTML.
                $mail->AltBody = "Hola {$usuario['nombre']}, usa este enlace para recuperar tu contraseña: $enlace";

                // Finalmente enviamos el correo.
                $mail->send();

                // Mensaje de éxito para mostrar al usuario.
                $mensaje = "Se ha enviado un correo con el enlace para recuperar tu contraseña.";
                $tipo_mensaje = "success";

            } catch (Exception $e) {
                // Si falla el envío, capturamos el error y lo mostramos.
                $mensaje = "No se pudo enviar el correo: " . $mail->ErrorInfo;
                $tipo_mensaje = "error";
            }
        }
    } else {
        // Si no encontramos un usuario con ese correo, mostramos error.
        $mensaje = "Ese correo no está registrado.";
        $tipo_mensaje = "error";
    }
}

// 2. VERIFICACIÓN DEL TOKEN (Paso 2)
// Variable para saber si el token en la URL es válido.
$token_valido = false;

// Verificamos si existe el parámetro 'token' en la URL (GET).
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Buscamos un usuario que tenga este token Y que la fecha de expiración sea mayor a AHORA (no expirado).
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->execute([$token]);
    // fetch() devolverá false si no encuentra coincidencias, o un array si el token es válido.
    $token_valido = $stmt->fetch(); 
}

// 3. CAMBIO DE CONTRASEÑA (Paso 3)
// Si el usuario envió el formulario con la nueva contraseña y el token oculto.
if (isset($_POST['nueva_password']) && isset($_POST['token'])) {

    // Recuperamos los datos del formulario POST.
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    $token = $_POST['token'];

    // Validamos que las dos contraseñas ingresadas sean idénticas.
    if ($nueva_password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";
    } else {
        // Volvemos a validar que el token sea correcto y no haya expirado justo antes de cambiar la clave.
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        // Si el token sigue siendo válido...
        if ($usuario) {
            // Encriptamos la nueva contraseña usando Bcrypt (por defecto).
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

            // Actualizamos la contraseña en la BD y limpiamos el token (lo hacemos NULL) para que no se pueda reusar.
            $stmt = $pdo->prepare("UPDATE usuarios 
                                   SET contrasena_hash = ?, reset_token = NULL, token_expira = NULL 
                                   WHERE id = ?");
            // Ejecutamos la actualización con la nueva clave encriptada y el ID del usuario.
            $stmt->execute([$password_hash, $usuario['id']]);

            // Mensaje de éxito.
            $mensaje = "Tu contraseña fue cambiada correctamente. Ya puedes iniciar sesión.";
            $tipo_mensaje = "success";
        } else {
            // Si el token ya no es válido.
            $mensaje = "El enlace ya expiró o no es válido.";
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
    <!-- Definimos la codificación de caracteres a UTF-8. -->
    <meta charset="utf-8"> 
    <!-- Título de la pestaña del navegador. -->
    <title>Recuperar contraseña MRMP</title>
    <!-- Enlazamos la hoja de estilos CSS específica para esta página. -->
    <link rel="stylesheet" href="recuperar.css"> 
    <!-- Meta tag para asegurar que sea responsivo en dispositivos móviles. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    </head>
<body>

<!-- Contenedor principal para centrar el contenido. -->
<div class="box">
<!-- Contenedor del formulario. -->
<div class="formulario">

<!-- Bloque PHP para mostrar mensajes de alerta (éxito o error). -->
<?php if ($mensaje): ?>
    <!-- Div dinámico que aplica clase 'success' o 'error' según el tipo de mensaje. -->
    <div class="message <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- Condicional PHP: Si no hay token válido y NO se está enviando un token... -->
<!-- Significa que estamos en el PASO 1: El usuario debe ingresar su correo. -->
<?php if (!$token_valido && !isset($_POST['token'])): ?>
    <div class="logo-taller">
     <!-- Logo de la empresa. -->
     <img src="img/mrmp-logo.png" alt="logo mrmp">
     <!-- Título y subtítulo explicativo. -->
     <p class="subtitulo">Recuperar Tu contraseña</p>
    <p>Ingresa tu correo para enviarte un enlace de recuperación.</p>
     </div>
    <!-- Formulario POST para enviar el correo. -->
    <form method="POST">
        <!-- Input para el email, requerido. -->
        <input type="email" name="correo" placeholder="Tu correo" required>
        <!-- Botón para enviar la solicitud. -->
        <button type="submit">Enviar enlace</button>
        <!-- Enlace para volver si el usuario recordó su contraseña. -->
        <a href="inicio_secion.php">Regresar al inicio de sesion</a>
    </form>
</div>
<!-- Si hay token válido o se está procesando el cambio... -->
<!-- Significa que estamos en el PASO 2: El usuario ingresa su nueva contraseña. -->
<?php else: ?>
    
    <form method="POST">
        <div class="logo-taller">
            <img src="img/mrmp-logo.png" alt="logo mrmp">
            <!-- Instrucción para el usuario. -->
            <p class="subtitulo">Escribe tu nueva contraseña (minimo 6 caracteres)</p>
        <!-- Campo oculto que envía el token junto con la contraseña. -->
        <!-- Usamos htmlspecialchars para prevenir XSS al imprimir el token en el HTML. -->
        <!-- El operador '??' usa el token de la URL (GET) o del POST anterior si hubo error. -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token']) ?>">
        
        <!-- Input para la nueva contraseña. -->
        <input type="password" name="nueva_password" placeholder="Nueva contraseña" minlength="6" required>
        <!-- Input para confirmar la contraseña. -->
        <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" minlength="6" required>
        
        <!-- Botón para confirmar el cambio. -->
        <button type="submit">Cambiar contraseña</button>
        <!-- Enlace para volver. -->
        <a href="inicio_secion.php">Regresar al inicio de sesion</a>
    </form>

<?php endif; ?>

</div>

</body>
</html>
