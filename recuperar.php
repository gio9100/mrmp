<?php
session_start();

/* ---------------------------------------------------------
   CONEXIÓN A LA BASE DE DATOS
--------------------------------------------------------- */
$host = 'localhost';
$dbname = 'bootcamp2';
$username = 'root';
$password = '';

try {
    // Conectamos a MySQL con PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}

/* ---------------------------------------------------------
   CARGAMOS PHPMailer (versión manual)
--------------------------------------------------------- */
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";
$tipo_mensaje = "";

/* ---------------------------------------------------------
   1) SOLICITUD DE RECUPERACIÓN (USUARIO ESCRIBE SU CORREO)
--------------------------------------------------------- */
if (isset($_POST['correo']) && !isset($_POST['nueva_password'])) {

    // Limpiamos el correo
    $correo = trim($_POST['correo']);

    // Buscamos si ese correo existe en la tabla
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if ($usuario) {

        // Generamos un token aleatorio único
        $token = bin2hex(random_bytes(32));

        // El token expira en 1 hora
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardamos el token en la tabla --> OJO: tu tabla usa reset_token
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, token_expira = ? WHERE correo = ?");
        if ($stmt->execute([$token, $expiracion, $correo])) {

            // ENLACE REAL DE RECUPERACIÓN
            $enlace = "http://localhost/mrmp/recuperar.php?token=$token";

            // Preparamos el email
            $mail = new PHPMailer(true);

            try {

                // Configuración SMTP de Gmail
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'giovannidossantos929@gmail.com'; 
                $mail->Password = 'ncxj opmh jwzy yjoz';  // Contraseña de aplicación
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Destinatarios
                $mail->setFrom('giovannidossantos929@gmail.com', 'Recuperación de Cuenta');
                $mail->addAddress($correo, $usuario['nombre']);

                // Contenido del correo
                $mail->isHTML(true);
                $mail->Subject = "Restablecer contraseña MRMP";

                // Cuerpo del mensaje en HTML
                $mail->Body = "
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
                    Este enlace expira en 1 hora.
                ";

                // Alternativa en texto plano
                $mail->AltBody = "Hola {$usuario['nombre']}, usa este enlace para recuperar tu contraseña: $enlace";

                // Enviamos el correo
                $mail->send();

                $mensaje = "Se ha enviado un correo con el enlace para recuperar tu contraseña.";
                $tipo_mensaje = "success";

            } catch (Exception $e) {
                $mensaje = "No se pudo enviar el correo: " . $mail->ErrorInfo;
                $tipo_mensaje = "error";
            }

        }

    } else {
        $mensaje = "Ese correo no está registrado.";
        $tipo_mensaje = "error";
    }
}

/* ---------------------------------------------------------
   2) CUANDO EL USUARIO YA HIZO CLICK EN EL ENLACE
--------------------------------------------------------- */
$token_valido = false;

if (isset($_GET['token'])) {

    $token = $_GET['token'];

    // Buscamos token válido que no haya expirado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
    $stmt->execute([$token]);
    $token_valido = $stmt->fetch();
}

/* ---------------------------------------------------------
   3) USUARIO YA ESCRIBE SU NUEVA CONTRASEÑA
--------------------------------------------------------- */
if (isset($_POST['nueva_password']) && isset($_POST['token'])) {

    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    $token = $_POST['token'];

    // Validamos que coincidan
    if ($nueva_password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";

    } else {

        // Buscamos el token válido
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = ? AND token_expira > NOW()");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();

        if ($usuario) {

            // Encriptamos la nueva contraseña
            $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

            // Guardamos la nueva contraseña y borramos el token
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
    <title>Recuperar contraseña MRMP</title>
    <link rel="stylesheet" href="recuperar.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
<body>

<div class="box">
<div class="formulario">


<?php if ($mensaje): ?>
    <div class="message <?= $tipo_mensaje ?>"><?= $mensaje ?></div>
<?php endif; ?>

<?php if (!$token_valido && !isset($_POST['token'])): ?>
    <div class="logo-taller">
     <img src="img/mrmp logo.png" alt="logo mrmp">
     <p class="subtitulo">Recuperar Tu contraseña</p>
    <p>Ingresa tu correo para enviarte un enlace de recuperación.</p>
     </div>
    <form method="POST">
        <input type="email" name="correo" placeholder="Tu correo" required>
        <button type="submit">Enviar enlace</button>
        <a href="inicio_secion.php">Regresar al inicio de sesion</a>
    </form>
</div>
<?php else: ?>

    
    <form method="POST">
        <div class="logo-taller">
            <img src="img/mrmp logo.png" alt="logo mrmp">
            <p class="subtitulo">Escribe tu nueva contraseña (minimo 6 caracteres)</p>
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
