<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use App\Models\Entrada;
use App\Models\Salida;
use App\Models\Producto;
use Cartalyst\Sentinel\Users\EloquentUser as Usuario;


class HomeAction extends Action
{
  public function index(Request $request, Response $response)
  {
    $this->logger->info('Home Page Index dispached');

    return $this->view->render($response, 'index.twig',
    [
      'ordenesEntrada'  => Entrada::count(),
      'ordenesSalida'   => Salida::count(),
      'usuarios'        => Usuario::count(),
      'productos'       => Producto::count(),
    ]);
  }
}
