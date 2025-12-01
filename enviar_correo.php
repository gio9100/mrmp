<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function enviarCorreo($destinatario, $asunto, $cuerpo) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Cambiar si usas otro proveedor
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mexicanracermp@gmail.com'; // PONER CORREO REAL AQUÍ O CONFIGURAR
        $mail->Password   = 'ynxm gxio tuku ssba'; // PONER CONTRASEÑA REAL
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('mexicanracermp@gmail.com', 'Mexican Racing Motor Parts');
        $mail->addAddress($destinatario);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;
        $mail->AltBody = strip_tags($cuerpo);

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error if needed: echo "Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>
