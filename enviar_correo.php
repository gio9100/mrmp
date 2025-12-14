<?php
// Importación de espacios de nombre (Namespaces) de PHPMailer
// Esto permite usar las clases sin escribir toda la ruta (PHPMailer\PHPMailer\PHPMailer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requiere los archivos de la librería PHPMailer manualmente
// Se asume que la carpeta 'PHPMailer' está en el mismo directorio
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// Función para enviar correos electrónicos de forma reutilizable
// $destinatario: Email de quien recibe
// $asunto: Título del correo
// $cuerpo: Contenido HTML del correo
function enviarCorreo($destinatario, $asunto, $cuerpo) {
    // Crea una nueva instancia de PHPMailer
    // true: Habilita el lanzamiento de excepciones en caso de error
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN DEL SERVIDOR SMTP ---
        // isSMTP: Indica que usaremos el protocolo SMTP
        $mail->isSMTP(); 
        // Host: Servidor SMTP de Gmail
        $mail->Host       = 'smtp.gmail.com'; 
        // SMTPAuth: Habilita autenticación SMTP (requerido por Gmail)
        $mail->SMTPAuth   = true; 
        // Username: Tu dirección de correo completa
        $mail->Username   = 'mexicanracermp@gmail.com'; 
        // Password: Tu contraseña de aplicación (App Password), NO tu contraseña normal
        $mail->Password   = 'ynxm gxio tuku ssba'; 
        // SMTPSecure: Tipo de encriptación (TLS es el estándar actual para puerto 587)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        // Port: Puerto TCP para conectarse (587 para TLS)
        $mail->Port       = 587; 

        // --- DESTINATARIOS ---
        // setFrom: Dirección y nombre del remitente (quien envía)
        $mail->setFrom('tucorreo@gmail.com', 'Performance Zone MX'); 
        // addAddress: Agrega el destinatario del correo
        $mail->addAddress($destinatario); 

        // --- CONTENIDO ---
        // isHTML(true): Permite usar etiquetas HTML en el cuerpo del correo
        $mail->isHTML(true); 
        // Subject: Asunto del correo
        $mail->Subject = $asunto; 
        // Body: Cuerpo principal del correo (puede tener <b>, <h1>, etc.)
        $mail->Body    = $cuerpo; 
        // AltBody: Cuerpo alternativo en texto plano para clientes de correo antiguos
        // strip_tags: Elimina las etiquetas HTML automáticamente
        $mail->AltBody = strip_tags($cuerpo); 

        // --- ENVÍO ---
        // send: Intenta enviar el correo
        $mail->send();
        return true; // Retorna verdadero si se envió correctamente

    } catch (Exception $e) {
        // En caso de error, captura la excepción y retorna falso
        // Se podría loguear $mail->ErrorInfo aquí
        return false;
    }
}
?>
