<?php
// session_start(): Inicia la sesión. Necesario si quisiéramos usar variables de sesión, aunque aquí se usa principalmente para consistencia.
session_start();

// Configuración manual de conexión a la Base de Datos (usando PDO en lugar de mysqli).
// Esto demuestra otra forma de conectar a bases de datos (PHP Data Objects).
$host = 'localhost';
$dbname = 'bootcamp2';
$username = 'root';
$password = '';

// try-catch: Estructura de control de excepciones. Intenta ejecutar el código 'try', y si falla, salta al 'catch'.
try {
    // new PDO(): Crea una nueva conexión PDO.
    // "mysql:host=...": DSN (Data Source Name) que define el driver y la base de datos.
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // setAttribute(): Configura el comportamiento de PDO.
    // PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION: Hace que los errores de SQL lancen "Excepciones" fatales
    // en lugar de simples advertencias, lo que permite capturarlas con try-catch.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // Si falla la conexión, mostramos el mensaje de error y terminamos el script (die).
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

// Requerimos las clases de PHPMailer para poder enviar correos.
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// use: Importamos los namespaces para no tener que escribir 'PHPMailer\PHPMailer\PHPMailer' cada vez.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";
$tipo_mensaje = "";

// --- PASO 1: SOLICITUD DE RECUPERACIÓN ---
// Si el usuario envió su correo (POST) y NO envió una nueva contraseña (significa que está en el paso 1).
if (isset($_POST['correo']) && !isset($_POST['nueva_password'])) {

    $correo = trim($_POST['correo']);

    // prepare(): Preparamos la consulta SELECT para buscar al usuario.
    // PDO usa '?' igual que mysqli para marcadores de posición.
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE correo = ?");
    
    // execute(): Ejecuta la consulta pasando un array con los valores para los '?'.
    $stmt->execute([$correo]);
    
    // fetch(): Obtiene la siguiente fila del conjunto de resultados. Devuelve false si no hay resultados.
    $usuario = $stmt->fetch();

    if ($usuario) {
        // bin2hex(random_bytes(32)): Genera un token aleatorio criptográficamente seguro de 64 caracteres hexadecimales.
        // random_bytes(32) crea 32 bytes de datos aleatorios, bin2hex lo convierte a texto legible.
        $token = bin2hex(random_bytes(32));

        // date(): Formatea la fecha/hora actual.
        // strtotime('+1 hour'): Calcula el timestamp de "dentro de 1 hora".
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // UPDATE: Guardamos el token y su fecha de expiración en la tabla del usuario.
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira = ? WHERE correo = ?");
        
        if ($stmt->execute([$token, $expiracion, $correo])) {

            // Construimos el enlace único que enviaremos por correo.
            $enlace = "http://localhost/mrmp/recuperar.php?token=$token";

            // Instanciamos PHPMailer para configurar el envío.
            $mail = new PHPMailer(true);

            try {
                // Configuración del servidor SMTP (Protocolo de envío de correo).
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true; // Activar autenticación
                $mail->Username = 'mexicanracermp@gmail.com'; 
                $mail->Password = 'ynxm gxio tuku ssba';  
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Encriptación TLS (seguridad)
                $mail->Port = 587; // Puerto estándar para TLS

                // Configuración del correo.
                $mail->setFrom('mexicanracermp@gmail.com', 'Restablecer password');
                $mail->addAddress($correo, $usuario['nombre']);

                // Contenido HTML.
                $mail->isHTML(true);
                $mail->Subject = "Restablecer password MRMP";
                
                // addEmbeddedImage: Incrusta una imagen directamente en el cuerpo del correo (CID).
                $mail->addEmbeddedImage('img/mrmp-logo.png', 'logoLab');

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

                // Cuerpo alternativo para clientes de correo que no soportan HTML.
                $mail->AltBody = "Hola {$usuario['nombre']}, usa este enlace para recuperar tu contraseña: $enlace";

                // send(): Envía el correo.
                $mail->send();

                $mensaje = "Se ha enviado un correo con el enlace para recuperar tu contraseña.";
                $tipo_mensaje = "success";

            } catch (Exception $e) {
                // $mail->ErrorInfo contiene detalles sobre por qué falló el envío.
                $mensaje = "No se pudo enviar el correo: " . $mail->ErrorInfo;
                $tipo_mensaje = "error";
            }
        }
    } else {
        $mensaje = "Ese correo no está registrado.";
        $tipo_mensaje = "error";
    }
}

// --- PASO 2: VERIFICACIÓN DEL TOKEN ---
// Este bloque se ejecuta cuando el usuario hace clic en el enlace del correo.
$token_valido = false;

// $_GET['token']: Captura el token de la URL (recuperar.php?token=xyz...)
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Consultamos si existe un usuario con ese token Y que el token no haya expirado yet (token_expira > NOW()).
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->execute([$token]);
    
    // Si devuelve una fila, el token es válido.
    $token_valido = $stmt->fetch(); 
}

// --- PASO 3: CAMBIO DE CONTRASEÑA ---
// Este bloque se ejecuta cuando el usuario envía el formulario de nueva contraseña.
if (isset($_POST['nueva_password']) && isset($_POST['token'])) {

    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    $token = $_POST['token'];

    // Validación básica de coincidencia.
    if ($nueva_password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";
    } else {
        // Volvemos a validar el token antes de hacer el cambio (seguridad doble).
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            // password_hash(): Generamos el hash seguro de la nueva clave.
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

            // UPDATE: Actualizamos la contraseña del usuario.
            // IMPORTANTE: También reseteamos 'reset_token' y 'token_expira' a NULL para que el enlace ya no se pueda reusar.
            $stmt = $pdo->prepare("UPDATE usuarios 
                                   SET contrasena_hash = ?, reset_token = NULL, token_expira = NULL 
                                   WHERE id = ?");
            $stmt->execute([$password_hash, $usuario['id']]);

            $mensaje = "Tu contraseña fue cambiada correctamente. Ya puedes iniciar sesión.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "El enlace ya expiró o no es válido.";
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
    <head>
    <meta charset="utf-8"> 
    <title>Recuperar Contraseña - Performance Zone MX</title>
    <link rel="stylesheet" href="recuperar.css"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    </head>
<body>

<div class="box">
<div class="formulario">

<!-- Mensaje de alerta (Éxito/Error) -->
<?php if ($mensaje): ?>
    <div class="message <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
<?php endif; ?>

<!-- PASO 1: Formulario para solicitar token (Correo) -->
<?php if (!$token_valido && !isset($_POST['token'])): ?>
    <div class="logo-taller">
            <img src="img/nuevologo.jpeg" alt="Logo Taller">
            <h1>Performance Zone MX</h1>
            <p class="subtitulo">Recuperación de Contraseña</p>
            <p>Ingresa tu correo para enviarte un enlace de recuperación.</p>
    </div>
    
    <!-- <form>: Enviar correo de recuperación. -->
    <form method="POST">
        <input type="email" name="correo" placeholder="Tu correo" required>
        <button type="submit">Enviar enlace</button>
        <a href="inicio_secion.php">Regresar al inicio de sesion</a>
    </form>
</div>

<!-- PASO 2: Formulario para cambiar contraseña (Token válido) -->
<?php else: ?>
    
    <!-- <form>: Establecer nueva contraseña. -->
    <form method="POST">
        <div class="logo-taller">
            <img src="img/mrmp-logo.png" alt="logo mrmp">
            <p class="subtitulo">Escribe tu nueva contraseña (minimo 6 caracteres)</p>

        <!-- <input type="hidden">: Envía el token de forma oculta. -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? $_POST['token']) ?>">
        
        <input type="password" name="nueva_password" placeholder="Nueva contraseña" minlength="6" required>
        <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" minlength="6" required>
        
        <button type="submit">Cambiar contraseña</button>
        <a href="inicio_secion.php">Regresar al inicio de sesion</a>
    </form>

<?php endif; ?>

</div>

</body>
</html>
