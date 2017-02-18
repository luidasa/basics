<?php

namespace App\Middleware;

use Slim\Http\Request;
use Slim\Http\Response;

use Cartalyst\Sentinel\Native\Facades\Sentinel;
use App\Models\Notificacion;

class NotificationMiddleware extends Middleware
{
    public function __invoke(Request $request, Response $response, $next)
    {
        $user = Sentinel::getUser();
        if (is_null($user)) {
          $notificaciones = Notificacion::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
          $this->view->getEnvironment()->addGlobal('notificaciones', $notificaciones);
        }
        $response = $next($request, $response);

        return $response;
    }
}


 ?>
