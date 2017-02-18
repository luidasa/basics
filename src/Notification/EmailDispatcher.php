<?php

namespace App\Notification;

/**
 *
 */
class EmailDispatcher
{
  protected $mail;

  function __construct($settings)
  {
    $this->mail = new \PHPMailer;

    $this->mail->SMTPDebug = $settings['smtp_debug'];               // Enable verbose debug output
    $this->mail->isSMTP();                                          // Set mailer to use SMTP
    $this->mail->Host = $settings['smtp_servers'];                  // Specify main and backup SMTP servers
    $this->mail->SMTPAuth = $settings['enabled_smtp_auth'];         // Enable SMTP authentication
    $this->mail->Username = $settings['smtp_username'];             // SMTP username
    $this->mail->Password = $settings['smtp_password'];             // SMTP password
    $this->mail->SMTPSecure = $settings['enabled_encryption'];      // Enable TLS encryption, `ssl` also accepted
    $this->mail->Port = $settings['tcp_port'];                      // TCP port to connect to
    $this->mail->setFrom(                                           // Set who the message is to be sent from
      $settings['email_remitente'],
      $settings['name_remitente']);
    $this->mail->addReplyTo(                                        // Set an alternative reply-to address
      $settings['email_reply'],
      $settings['name_reply']);
  }

/**
  Los destinatarios son del tipo array nombrado donde la llave es el email y el valor es el nombre.
  $conCopia son del tipo array nombrado donde la llave es el email y el valor es el nombre.
  $conCopiaOculta son del tipo array nombrado donde la llave es el email y el valor es el nombre
  $archivos son del tipo array nombrado donde la llave es el archivo fisico y el valor es el nombre del archivo.
  $asunto Es el asunto del mensaje
  $mensajeHtml Es el cuerpo del mensaje en formato HTML
  $mensajeText Es el cuerpo del mensaje en formato Texto plano.
*/
  public function send($destinatarios, $conCopia, $conCopiaOculta, $archivos, $asunto, $mensajeHtml, $mensajeText) {
    $error = NULL;
    foreach($destinatarios as $key => $value) {
      $this->mail->addAddress($key, $value);
    }
    foreach($conCopia as $key=>$value){
      $this->mail->addCC($key, $value);
    }
    foreach($conCopiaOculta as $key=>$value){
      $this->mail->addBCC($key, $value);
    }
    foreach($archivos as $key=>$value){
      $this->mail->addAttachment($key, $value);
    }
    $this->mail->isHTML(true);                                  // Set email format to HTML
    $this->mail->Subject = $asunto;
    $this->mail->Body    = $mensajeHtml;
    $this->mail->AltBody = $mensajeText;

    if(!$this->mail->send()) {
        $error = $this->mail->ErrorInfo;
    }
    return $error;
  }
}


 ?>
