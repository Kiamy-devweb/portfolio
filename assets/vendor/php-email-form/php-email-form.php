<?php
class PHP_Email_Form {
  public $to;
  public $from_name;
  public $from_email;
  public $subject;
  public $smtp;
  public $ajax;

  private $messages = [];

  public function add_message($content, $label = '', $priority = 0) {
    $this->messages[] = "$label: $content";
  }

  public function send() {
    $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
    $headers .= "Reply-To: {$this->from_email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $body = implode("\n", $this->messages);

    // Usar função mail() simples
    if (mail($this->to, $this->subject, $body, $headers)) {
      return 'OK';
    } else {
      return 'Erro ao enviar';
    }
  }
}
?>
