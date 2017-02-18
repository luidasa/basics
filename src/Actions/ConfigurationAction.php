<?php

namespace VisionWap\Actions;

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
        return $this->view->render($response, 'configuration.twig');
    }

    public function postEdit(Request $request, Response $response)
    {
        return $this->view->render($response, 'configuration.twig');
    }

    public function getStart(Request $request, Response $response, $args) {

    }

    public function postStart(Request $request, Response $response, $args) {

    }
}
