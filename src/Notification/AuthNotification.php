<?php

namespace App\Notification;

/**
  Se encarga de coordinar las notificaciones por email que se realizan a los usuarios.
*/
class AuthNotification
{
  private $container;

  public function __construct($c) {
    $this->container = $c;
  }

/**
  Envia un email para indicar que se ha registrado el usuario.
*/
  public function signUp($user, $token) {
    $destinatarios[$user->email] = $user->first_name;
    $conCopia = $conCopiaOculta = $archivos = array();
    $asunto = "Registro de usuario" ;
    $url =
      $this->container->request->getUri()->getScheme() . '://' .
      $this->container->request->getUri()->getHost() . ':' .
      $this->container->request->getUri()->getPort() .
      $this->container->router->pathFor('auth.signup.activate', ['id'=>$user->id, 'code'=>$token]);
    $mensajeHtml = "Estimado $user->first_name, Oprime <a href='$url'>AQUI</a> para completar tu registro o copia y pega esta dirección $url en tu explorador.";
    $mensajeText = "Estimado $user->first_name, Copia y pega esta dirección $url en la barra de direccion de tu explorador de internet para completar tu registro.";
    $errores = $this->container->mailer->send($destinatarios, $conCopia, $conCopiaOculta, $archivos, $asunto, $mensajeHtml, $mensajeText);
    $this->container->logger->debug($errores);
    return $errores;
  }

/**
  Envia un email para indicar que se esta solicitando la restauración de la contraseña.
*/
  public function resetPasswordRequest($user, $token) {
    $destinatarios[$user->email] = $user->first_name;
    $conCopia = $conCopiaOculta = $archivos = array();
    $asunto = "Recuperación de Contraseña" ;
    $url =
      $this->container->request->getUri()->getScheme() . '://' .
      $this->container->request->getUri()->getHost() . ':' .
      $this->container->request->getUri()->getPort() .
      $this->container->router->pathFor('auth.password.update', ['id'=>$user->id, 'code'=>$token]);
    $mensajeHtml = "Estimado $user->first_name, Oprime  <a href='$url'>AQUI</a>, o pega esta dirección $url en tu explorador para completar tu cambio de contraseña.";
    $mensajeText = "Estimado $user->first_name, Oprime  <a href='$url'>AQUI</a>, o pega esta dirección $url en tu explorador para completar tu cambio de contraseña.";
    $errores = $this->container->mailer->send($destinatarios, $conCopia, $conCopiaOculta, $archivos, $asunto, $mensajeHtml, $mensajeText);
    $this->container->logger->debug($errores);
    return $errores;
  }

/**
  Se envia un correo para indicar que se estan borrando los datos del usuario.
*/
  public function signDown($user) {

  }
}


 ?>
