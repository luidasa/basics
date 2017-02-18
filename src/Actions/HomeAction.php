<?php

namespace VisionWap\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use Cartalyst\Sentinel\Users\EloquentUser as Usuario;


class HomeAction extends Action
{
  public function index(Request $request, Response $response)
  {
    $this->logger->info('Home Page Index dispached');

    return $this->view->render($response, 'index.twig');
  }
}
