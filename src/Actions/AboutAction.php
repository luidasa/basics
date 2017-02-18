<?php

namespace VisionWap\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AboutAction.
 */
class AboutAction extends Action
{
    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function aboutPage(Request $request, Response $response)
    {
        $this->view->getEnvironment()->addGlobal('template', 'templatePage');

        return $this->view->render($response, 'about.twig');
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function termsPage(Request $request, Response $response)
    {
        $this->view->getEnvironment()->addGlobal('template', 'templatePage');

        return $this->view->render($response, 'terms.twig');
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function privacyPage(Request $request, Response $response)
    {
        $this->view->getEnvironment()->addGlobal('template', 'templatePage');

        return $this->view->render($response, 'privacy.twig');
    }
}
