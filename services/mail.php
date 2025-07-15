<?php

namespace Utils\Services;

use SplitPHP\Service;
use PHPMailer\PHPMailer\PHPMailer;

class Mail extends Service
{
  public function init(): void
  {
    $this->setConfigs();
    require ROOT_PATH . '/vendors/phpmailer/src/Exception.php';
    require ROOT_PATH . '/vendors/phpmailer/src/PHPMailer.php';
    require ROOT_PATH . '/vendors/phpmailer/src/SMTP.php';
  }

  public function send($msg, $recipientAddress, $subject, $isHTML = true)
  {
    $host     = SMTP_HOST;
    $port     = SMTP_PORT;
    $username = SMTP_USER;
    $password = SMTP_PASS;
    $from     = SENDER_EMAIL;
    $tls      = REQUIRE_TLS;
    $from_name = SENDER_NAME;

    $mailer = new PHPMailer(true);
    $mailer->IsSMTP();
    $mailer->SMTPDebug = 0;
    $mailer->Port = $port; // Indica a porta de conexão para a saída de e-mails. Utilize obrigatoriamente a porta 587.
    $mailer->Host = $host; // Onde em 'servidor_de_saida' deve ser alterado por um dos hosts abaixo:

    if ($tls)
      $mailer->SMTPSecure = 'tls';

    $mailer->CharSet    = "UTF-8";
    $mailer->SMTPAuth   = true;           // Define se haverá ou não autenticação no SMTP
    $mailer->Username   = $username;      // Informe o e-mai o completo
    $mailer->Password   = $password;      // Senha da caixa postal
    $mailer->FromName   = $from_name;     // Nome que será exibido para o destinatário
    $mailer->From       = $from;          // Obrigatório ser a mesma caixa postal indicada em "username"
    $mailer->AddAddress($recipientAddress);  // Destinatários
    $mailer->Subject    = $subject;
    $mailer->Body       = $msg;
    $mailer->IsHTML($isHTML);

    if (!$mailer->Send()) {
      return false;
    } else {
      return true;
    }
  }

  private function setConfigs()
  {
    if (!defined('SMTP_HOST'))
      define('SMTP_HOST', getenv('SMTP_HOST'));
    if (!defined('SMTP_PORT'))
      define('SMTP_PORT', getenv('SMTP_PORT'));
    if (!defined('REQUIRE_TLS'))
      define('REQUIRE_TLS', getenv('REQUIRE_TLS') == 'on');
    if (!defined('SMTP_USER'))
      define('SMTP_USER', getenv('SMTP_USER'));
    if (!defined('SMTP_PASS'))
      define('SMTP_PASS', getenv('SMTP_PASS'));
    if (!defined('SENDER_EMAIL'))
      define('SENDER_EMAIL', getenv('SENDER_EMAIL'));
    if (!defined('SENDER_NAME'))
      define('SENDER_NAME', getenv('SENDER_NAME'));
  }
}
