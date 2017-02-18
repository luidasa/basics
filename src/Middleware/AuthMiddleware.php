<?php

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

use Cartalyst\Sentinel\Native\Facades\Sentinel;

class AuthMiddleware extends Middleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
      $user = Sentinel::getUser();
      if (is_null($user)) {
        $this->container->flash->addMessage('error', 'Por favor inicia tu sesión.');
        return $response->withRedirect($this->container->router->pathFor('auth.signin'));
      }
      $this->container->view->getEnvironment()->addGlobal('operador', $user);
      $response = $next($request, $response);

      return $response;
    }
}
