<?php

namespace VisionWap\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

class GuestMiddleware extends Middleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        if ($this->container->AuthAction->check()) {
            $this->container->flash->addMessage('error', 'Ups!! Ya estas en sesión seguro estas buscando esto? si es asi primero debes cerrar la sesión activa.');
            return $response->withRedirect($this->container->router->pathFor('home'));
        }
        $response = $next($request, $response);
        return $response;
    }
}
