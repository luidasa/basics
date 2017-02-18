<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;
use Respect\Validation\Validator as v;
use Cartalyst\Sentinel\Native\Facades\Sentinel;

/**
* Class Manejo de la sesión de un usuario.
*/
class AuthAction extends Action
{
  public function showSettings(Request $request, Response $response)
  {
    $this->logger->info('Settings Page Dispatched');
    return $this->view->render($response, 'settings.twig');
  }

  public function updateSettings(Request $request, Response $response)
  {
    $this->logger->info('Settings Page Processed');
  }

  public function showProfile(Request $request, Response $response)
  {
    $this->logger->info('Profile Page Dispatched');
    $this->view->getEnvironment()->addGlobal('operador', Sentinel::getUser());
    return $this->view->render($response, 'profile.twig');
  }

  public function updateProfile(Request $request, Response $response)
  {
    $this->logger->info('Profile Page Processed');
    $validation = $this->validator->validate($request, [
      'nombre' => v::notEmpty()->alnum(),
      'apellidos' => v::notEmpty()->alnum(),
      'email' => v::noWhitespace()->notEmpty()->email(),
      'password' => v::optional(v::noWhitespace()->notEmpty()),
      'repetir' => v::optional(v::noWhitespace()->notEmpty()->identical($request->getParam('password')))
    ]);
    if ($validation->failed()) {
      foreach($validation->errores as $error ) {
        $this->flash->addMessage('error', $error);
      }
    } else {
      $credentials =
      [
        'first_name' => $request->getParam('nombre'),
        'last_name' => $request->getParam('apellidos'),
        'email' => $request->getParam('email')
      ];
      if (!empty($request->getParam('password'))) {
        $credentials['password'] = $request->getParam('password');
      }
      $usuarioActual = Sentinel::getUser();
      $usuarioActual = Sentinel::update($usuarioActual, $credentials);
      $this->logger->debug("Datos del usuario actualizados.");
      $this->flash->addMessage('success', 'Hemos actualizado los datos de tu perfil. Si actualizaste tu contraseña no olvides utilizar tu nueva contraseña la siguiente vez que inicies sesión.');
     }
     return $response->withRedirect($this->router->pathFor('auth.profile' ));
  }

  public function activateSignUp(Request $request, Response $response)
  {
    $this->logger->info('Activate SignUp Page Processed');
    $route = $request->getAttribute('route');
    $id = $route->getArgument('id');
    $codigo = $route->getArgument('code');
    $url = $this->router->pathFor('auth.signup');
    if (!($user = Sentinel::findById($id))) {
      $this->flash->addMessage('error', 'No hemos localizado tu usuario.');
    } else if ($this->Activation->complete($user, $codigo)) {
      $this->flash->addMessage('success', 'Usuario activado ahora puedes iniciar sesión con tu email y contraseña.');
      $url = $this->router->pathFor('auth.signin');
    } else if ($this->Activation->completed($user)) {
      $this->flash->addMessage('info', 'Tu usuario ya estaba activado previamente. Si no recuerdas tu contraseña recuperala.');
      $url = $this->router->pathFor('auth.password.change');
    }
    return $response->withStatus(302)->withHeader('Location', $url);
  }

  public function postSignUp(Request $request, Response $response)
  {
    $this->logger->info('SignUp Page Processed');
    $validation = $this->validator->validate($request, [
      'nombre' => v::notEmpty()->alnum(),
      'apellido' => v::notEmpty()->alnum(),
      'email' => v::noWhitespace()->notEmpty()->email(),
      'password' => v::noWhitespace()->notEmpty(),
      'repetir' => v::noWhitespace()->notEmpty()->identical($request->getParam('password')),
      'acepto'=> v::notEmpty(),
    ]);
    if ($validation->failed()) {
      foreach($validation->errores as $error ) {
        $this->flash->addMessage('error', $error);
      }
    } else {
      $credentials =
      [
        'first_name' => $request->getParam('nombre'),
        'last_name' => $request->getParam('apellido'),
        'email' => $request->getParam('email'),
        'password' => $request->getParam('password'),
      ];
      try {
        $user = Sentinel::register($credentials);
        $activation = $this->Activation->create($user);
        $this->AuthNotifications->signUp($user, $activation->code);
        $this->flash->addMessage('success', 'Revisa tu correo eletrónico para completar tu registro.');
      } catch (\Illuminate\Database\QueryException $ex) {
        $this->flash->addMessage('error', 'Ya tenemos registrado ese correo electrónico.');
      }
    }

    return $response->withStatus(302)->withHeader('Location', $this->router->pathFor('auth.signup'));
  }

  /**
  * Muestra la forma para registrarse como un nuevo usuario.
  */
  public function getSignUp(Request $request, Response $response)
  {
    $this->logger->info('SignUp Page Dispatched');
    return $this->view->render($response, 'signup.twig');
  }

  /**
  * Muestra la forma para recuperar la contraseña.
  */
  public function getRecoverPassword(Request $request, Response $response)
  {
    $this->logger->info('Recover Password Page Dispatched');
    return $this->view->render($response, 'recover.twig');
  }

  public function postRecoverPassword(Request $request, Response $response)
  {
    $this->logger->info('Recover Password Page Processed');
    $url = $this->router->pathFor('auth.password.change');
    $validation = $this->validator->validate($request, [
      'email' => v::noWhitespace()->notEmpty()->email(),
    ]);
    $credentials = ['login' => $request->getParam('email')];
    if ($validation->failed()) {
      foreach($validation->errores as $error ) {
        $this->flash->addMessage('error', $error);
      }
    } else if ($user = Sentinel::findUserByCredentials($credentials)) {
      $reminder = $this->Reminder->create($user);
      $this->AuthNotifications->resetPasswordRequest($user, $reminder->code);
      $this->flash->addMessage('success', 'Hemos enviado las instrucciones a tu correo para recuperar tu contraseña.');
      $url = $this->router->pathFor('auth.signin');
    } else {
      $this->flash->addMessage('error', 'El email no lo tenemos registrado. Registrate ahora para poder ingresar a nuestro portal.');
    }
    return $response->withRedirect($url);
  }

  public function updatePasswordView(Request $request, Response $response)
  {
    $this->logger->info('Change Password Page Dispatched');
    $route = $request->getAttribute('route');
    $id = $route->getArgument('id');
    $codigo = $route->getArgument('code');
    $this->view->getEnvironment()->addGlobal('id', $id);
    $this->view->getEnvironment()->addGlobal('code', $codigo);
    return $this->view->render($response, 'password.twig');
  }

  public function updatePassword(Request $request, Response $response)
  {
    $this->logger->info('Change Password Processed');
    $route = $request->getAttribute('route');
    $id = $route->getArgument('id');
    $codigo = $route->getArgument('code');
    $url = $this->router->pathFor('auth.password.change');
    $validation = $this->validator->validate($request, [
      'password' => v::noWhitespace()->notEmpty(),
      'repetir' => v::noWhitespace()->notEmpty()->identical($request->getParam('password'))
    ]);
    if ($validation->failed()) {
      foreach($validation->errores as $error ) {
        $this->flash->addMessage('error', $error);
      }
    } else {
      $user = Sentinel::findUserById($id);
      if ($reminder = $this->Reminder->complete($user, $codigo, $request->getParam('password'))) {
        $this->flash->addMessage('success', 'Hemos actualizado tu contraseña, ahora puedes ingresar utilizando tu correo y tu nueva contraseña.');
        $url = $this->router->pathFor('auth.signin');
      } else {
        $this->flash->addMessage('error', 'El codigo que nos proporcionaste ha expirado vuelve a solicitar uno.');
      }
    }
    return $response->withRedirect($url);
  }

  public function signOut(Request $request, Response $response)
  {
    $this->logger->info('SignOut Page Dispatched');
    Sentinel::logout();
    return $response->withRedirect($this->router->pathFor('auth.signin'));
  }

  public function getSignIn(Request $request, Response $response)
  {
    $this->logger->info('SignIn Page Index Dispatched');
    return $this->view->render($response, 'login.twig');
  }

  public function postSignIn(Request $request, Response $response)
  {
    $this->logger->info('SignIn Page Processed');
    $validation = $this->validator->validate($request, [
      'email' => v::noWhitespace()->notEmpty()->email(),
      'password' => v::noWhitespace()->notEmpty(),
    ]);
    $url = $this->router->pathFor('auth.signin');
    if ($validation->failed()) {
      foreach($validation->errores as $error ) {
        $this->flash->addMessage('error', $error);
      }
    } else {
      $remember = $request->getParam('remember');
      $credenciales = ['email' => $request->getParam('email'), 'password' => $request->getParam('password')];
      try {
        if ($remember) {
          $loginOk = Sentinel::authenticateAndRemember($credenciales);
        } else {
          $loginOk = Sentinel::authenticate($credenciales);
        }
        if (!$loginOk) {
          $this->logger->debug("Fallo la autenticación [$loginOk] con las credenciales");
          $this->flash->addMessage('error', 'No hemos podido verificar tus credenciales.');
        } else {
          $this->flash->addMessage('success', 'Bienvenido!!!');
          $url = $this->router->pathFor('home');
        }
      } catch (NotActivatedException $ex) {
        $this->logger->error($ex, "No esta activado el usuario " . $request->getParam('email'));
        $this->flash->addMessage('error', 'Aun no has activado tu cuenta. Verifica tu bandeja de correo para confirmar tu registro.');
      } catch (Exception $ex) {
        $this->logger->error($ex, "Actividad sospechosa del usuario " . $request->getParam('email'));
        $this->flash->addMessage('error', 'Has excedido el numero de intentos para iniciar sesión. Para tu seguridad bloquearemos temporalmente tu usuario.');
      }
    }
    return $response->withRedirect($url);
  }

  /**
  * Verifica se existe una sesión de usuario activa.
  */
  public function check()
  {
    $usuario = Sentinel::getUser();
    return isset( $usuario );
  }

  public function isAdministrator()
  {
    return true;
  }
}
