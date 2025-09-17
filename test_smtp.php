<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'assets/vendor/phpmailer/src/Exception.php';
require 'assets/vendor/phpmailer/src/PHPMailer.php';
require 'assets/vendor/phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'kiamy.webdev@gmail.com';
    $mail->Password = 'kmum ezqh hnll hqvy';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('kiamy.webdev@gmail.com', 'Teste SMTP');
    $mail->addAddress('kiamy.webdev@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Teste de SMTP';
    $mail->Body    = 'Se recebes este email, o SMTP está a funcionar.';

    $mail->send();
    echo "✅ Email enviado com sucesso!";
} catch (Exception $e) {
    echo "❌ Erro: {$mail->ErrorInfo}";
}
