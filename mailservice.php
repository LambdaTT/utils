<?php

namespace application\services\utils;

use \engine\Service;
use \Exception;

class Mailservice extends Service
{
  private const MAILSERVICE_DNS = 'http://172.31.41.181';
  private const SENDER_EMAIL = 'system@mqvending.com.br';

  public function send($msg, $recipientEmail, $subject)
  {
    $data = [
      'content' => $msg,
      'subject' => $subject,
      'mailTo' => $recipientEmail,
      'fromEmail' => self::SENDER_EMAIL,
      'fromName' => APPLICATION_NAME
    ];

    $curlRes = $this->getService('utils/curl')
      ->setHeader('Ds-Domain: ' . URL_APPLICATION)
      ->setHeader('Ds-Appsecret: ' . PUBLIC_KEY)
      ->setData($data)
      ->post(self::MAILSERVICE_DNS . '/api/mailing/v1/send');

    if ($curlRes->status != 201) throw new Exception("An error occurred on the attempt to send an email through mailservice.");
  }
}
