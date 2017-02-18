<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AboutAction.
 */
class ConfigurationAction extends Action
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function edit(Request $request, Response $response)
    {
        $this->view->getEnvironment()->addGlobal('template', 'templatePage');
        return $this->view->render($response, 'configuration.twig');
    }

    public function start(Request $request, Response $response, $args) {
      $parameters = $request->getQueryParams();
      $nombre = $parameters['nombre'];
      $apellido = $parameters['apellido'];
      $email = $parameters['email'];
      $password = $parameters['password'];

      drop();
      create();
      inicialize(
        $nombre,
        $apellido,
        $email,
        $password
      );
    }
}
