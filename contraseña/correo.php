<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requiere las clases de PHPMailer (asegúrate de que están en la carpeta PHPMailer/)
require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

function enviarCorreo($destinatario, $enlace) {
    $mail = new PHPMailer(true);


    

    try {
        // Configurar el servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Hotmail/Outlook. Para Gmail usa: smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jeisonhitler81@gmail.com'; // Reemplaza con tu correo
        $mail->Password   = 'lvej zhtf rwop gevw';         // Reemplaza con tu contraseña o app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Remitente y destinatario
        $mail->setFrom('jeisonhitler81@gmail.com', 'Soporte Almacén');
        $mail->addAddress($destinatario);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña';
        $mail->Body    = "<p>Hola,</p>
                          <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
                          <p><a href='$enlace'>$enlace</a></p>
                          <p>Este enlace caduca en 1 hora.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Para depuración puedes activar esto:
        // echo 'Error al enviar correo: ', $mail->ErrorInfo;
        return false;
    }
}
?>

