<?php

namespace VisionWap\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

use Cartalyst\Sentinel\Native\Facades\Sentinel;

class AutorizationMiddleware extends Middleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $user = Sentinel::getUser();
        $ruta = $request->getUri()->getPath();
        $permiso = substr(str_replace('/', '.', $ruta), 1);
        $permiso = preg_replace('/[0-9]+/', '', $permiso);
        while (substr($permiso, -1) == '.') {
          $permiso = substr($permiso, 0, strlen($permiso) - 1);
        }
        if (!$user->hasAccess([$permiso])) {
          $this->container->flash->addMessage('error', "No tienes permisos para acceder a esta ruta. [$permiso]");
          return $response->withRedirect($this->container->router->pathFor('home'));
        }
        $response = $next($request, $response);

        return $response;
    }
}
