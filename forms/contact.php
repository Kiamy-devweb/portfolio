<?php
session_start();

// Configurações
$receiving_email_address = 'kiamy.webdev@gmail.com';
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'username' => 'kiamy.webdev@gmail.com',
    'password' => 'kmum ezqh hnll hqvy', // App Password do Gmail
    'port' => 600,
    'encryption' => 'tls'
];

// Headers de segurança - compatível com validate.js
header('Content-Type: text/plain; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Verificar se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Método não permitido';
    exit;
}

// Rate limiting simples (opcional - requer sessões)
if (isset($_SESSION['last_submit']) && (time() - $_SESSION['last_submit']) < 30) {
    http_response_code(429);
    echo 'Aguarde 30 segundos antes de enviar outra mensagem';
    exit;
}

try {
    // Carregar PHPMailer - versão download manual
    $phpmailer_path = '../assets/vendor/phpmailer/src/';
    
    if (!file_exists($phpmailer_path . 'PHPMailer.php')) {
        throw new Exception('PHPMailer não encontrado na pasta: ' . $phpmailer_path);
    }

    // Carregar os arquivos necessários
    require_once $phpmailer_path . 'Exception.php';
    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';

    // Função de validação
    function validateInput($data, $type = 'text') {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        
        switch($type) {
            case 'email':
                return filter_var($data, FILTER_VALIDATE_EMAIL) ? $data : false;
            case 'phone':
                return preg_match('/^[+]?[0-9\s\-\(\)]{7,20}$/', $data) ? $data : false;
            case 'text':
                return strlen($data) > 0 && strlen($data) <= 100 ? $data : false;
            case 'message':
                return strlen($data) > 0 && strlen($data) <= 1000 ? $data : false;
            default:
                return $data;
        }
    }

    // Validar dados obrigatórios
    $name = validateInput($_POST['name'] ?? '', 'text');
    $email = validateInput($_POST['email'] ?? '', 'email');
    $subject = validateInput($_POST['subject'] ?? '', 'text');
    $message = validateInput($_POST['message'] ?? '', 'message');
    $phone = isset($_POST['phone']) ? validateInput($_POST['phone'], 'phone') : '';

    // Verificar campos obrigatórios
    $errors = [];
    if (!$name) $errors[] = 'Nome é obrigatório e deve ter até 100 caracteres';
    if (!$email) $errors[] = 'Email válido é obrigatório';
    if (!$subject) $errors[] = 'Assunto é obrigatório e deve ter até 100 caracteres';
    if (!$message) $errors[] = 'Mensagem é obrigatória e deve ter até 1000 caracteres';
    
    if (!empty($errors)) {
        http_response_code(400);
        echo implode('. ', $errors);
        exit;
    }

    // Verificação básica anti-spam
    $spam_keywords = ['viagra', 'casino', 'lottery', 'winner', 'prize', 'million'];
    $content_check = strtolower($name . ' ' . $subject . ' ' . $message);
    
    foreach ($spam_keywords as $keyword) {
        if (strpos($content_check, $keyword) !== false) {
            http_response_code(400);
            echo 'Mensagem rejeitada pelo filtro anti-spam';
            exit;
        }
    }

    // Criar instância do PHPMailer (sem namespace pois carregamos manualmente)
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host = $smtp_config['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_config['username'];
    $mail->Password = $smtp_config['password'];
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_config['port'];
    $mail->CharSet = 'UTF-8';

    // Configurações adicionais SMTP
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Configurar remetente e destinatário
    $mail->setFrom($smtp_config['username'], 'Portfolio Contact Form');
    $mail->addAddress($receiving_email_address);
    $mail->addReplyTo($email, $name);

    // Conteúdo do email
    $mail->isHTML(true);
    $mail->Subject = "Novo contacto: " . $subject;
    
    // Template HTML do email
    $html_body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
            <h2 style='color: #333; margin-top: 0;'>Nova Mensagem do Portfolio</h2>
        </div>
        
        <table style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold; width: 100px;'>Nome:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($name) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Email:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($email) . "</td>
            </tr>";
            
    if ($phone) {
        $html_body .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Telefone:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($phone) . "</td>
            </tr>";
    }
    
    $html_body .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;'>Assunto:</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($subject) . "</td>
            </tr>
            <tr>
                <td style='padding: 10px; font-weight: bold; vertical-align: top;'>Mensagem:</td>
                <td style='padding: 10px;'>" . nl2br(htmlspecialchars($message)) . "</td>
            </tr>
        </table>
        
        <div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;'>
            <p><strong>Informações do envio:</strong></p>
            <p>Data: " . date('d/m/Y H:i:s') . "</p>
            <p>IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</p>
            <p>User Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "</p>
        </div>
    </div>";

    $mail->Body = $html_body;
    
    // Versão texto alternativa
    $text_body = "Nova mensagem do portfolio\n\n";
    $text_body .= "Nome: " . $name . "\n";
    $text_body .= "Email: " . $email . "\n";
    if ($phone) $text_body .= "Telefone: " . $phone . "\n";
    $text_body .= "Assunto: " . $subject . "\n\n";
    $text_body .= "Mensagem:\n" . $message . "\n\n";
    $text_body .= "---\n";
    $text_body .= "Enviado em: " . date('d/m/Y H:i:s') . "\n";
    $text_body .= "IP: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'N/A');
    
    $mail->AltBody = $text_body;

    // Enviar email
    if ($mail->send()) {
        // Registrar último envio
        $_SESSION['last_submit'] = time();
        
        // Log de sucesso (opcional)
        error_log("Contact form submitted successfully from: " . $email);
        
        // Retorna OK para compatibilidade com validate.js
        http_response_code(200);
        echo 'OK';
    } else {
        throw new Exception('Erro ao enviar email: ' . $mail->ErrorInfo);
    }

} catch (Exception $e) {
    // Log do erro
    error_log("Contact form error: " . $e->getMessage());
    
    http_response_code(500);
    echo 'Erro interno do servidor. Tente novamente mais tarde.';
}
?>