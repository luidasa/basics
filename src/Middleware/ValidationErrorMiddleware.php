<?php

namespace App\Middleware;

use Slim\Http\Response;
use Slim\Http\Request;

class ValidationErrorMiddleware extends Middleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        if (isset($_SESSION['errors'])) {
            $this->container->view->getEnvironment()->addGlobal('errors', $_SESSION['errors']);
            unset($_SESSION['errors']);
        }

        $response = $next($request, $response);
        return $response;
    }
}
