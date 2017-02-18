<?php
// DIC configuration

use Slim\Container;
use Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
use Cartalyst\Sentinel\Reminders\IlluminateReminderRepository;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Respect\Validation\Validator as v;

$container = $app->getContainer();

// -----------------------------------------------------------------------------
// Service providers
// -----------------------------------------------------------------------------

// Error Handler
$container['errorHandler'] = function (Container $c) {
    return function ($request, $response, $exception) use ($c) {

        return $c['view']->render($response, '500.twig')->withStatus(500);
    };
};

// Error Handler
$container['notFoundHandler'] = function (Container $c) {
    return function ($request, $response, $exception) use ($c) {
        return $c['view']->render($response, '404.twig')->withStatus(404);
    };
};

// Flash messages
$container['flash'] = function (Container $c) {
    return new \Slim\Flash\Messages();
};

// Twig
$container['view'] = function (Container $c) {
    $settings = $c->get('settings');
    $view = new \Slim\Views\Twig($settings['view']['template_path'], $settings['view']['twig']);

    // Add extensions
    $view->addExtension(new Slim\Views\TwigExtension(
        $c->get('router'),
        $c->get('request')->getUri()
    ));

    $view->getEnvironment()->addGlobal('flash', $c->flash);

    return $view;
};

// CSRF
$container['csrf'] = function (Container $c) {
    $guard = new \Slim\Csrf\Guard();
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf", false);
        return $response->write(<<<EOT
<!DOCTYPE html>
<html>
<head><title>CSRF</title></head>
<body>
    <h1>Error</h1>
    <p>A ocurrido un error al intentar reenviar el formulario.
       Por favor intentelo mas tarde.</p>
</body>
</html>
EOT
        );
//->write('error!!!'); //$next($request, $response);
    });
    return $guard;
};

// database
$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection($config['settings']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// -----------------------------------------------------------------------------
// Service factories
// -----------------------------------------------------------------------------

// monolog
$container['logger'] = function (Container $c) {
    $settings = $c->get('settings');
    $logger = new Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['logger']['path'], Monolog\Logger::DEBUG));
    return $logger;
};

// Eloquent, la capsula ya debe estar configurada.
$container['db'] = function (Container $c) use ($capsule) {
    return $capsule;
};

// -----------------------------------------------------------------------------
// Action factories¡
// -----------------------------------------------------------------------------

$container['HomeAction'] = function (Container $c) {
    return new App\Actions\HomeAction($c);
};

$container['AboutAction'] = function (Container $c) {
    return new App\Actions\AboutAction($c);
};

$container['ContactAction'] = function (Container $c) {
    return new App\Actions\ContactAction($c);
};

$container['AuthAction'] = function (Container $c) {
    return new App\Actions\AuthAction($c);
};

$container['AccountAction'] = function (Container $c) {
    return new App\Actions\AccountAction($c);
};

$container['RegistrationOrderAction'] = function (Container $c) {
    return new App\Actions\RegistrationOrderAction($c);
};

$container['ExtractionAction'] = function (Container $c) {
    return new App\Actions\ExtractionAction($c);
};

$container['SearchAction'] = function (Container $c) {
    return new App\Actions\SearchAction($c);
};

$container['ShipmentAction'] = function (Container $c) {
    return new App\Actions\ShipmentAction($c);
};

$container['ConfigurationAction'] = function (Container $c) {
    return new App\Actions\ConfigurationAction($c);
};

$container['AuthNotifications'] = function (Container $c) {
    return new App\Notification\AuthNotification($c);
};

$container['Activation'] = function (Container $c) {
    return new IlluminateActivationRepository();
};

$container['Reminder'] = function (Container $c) {
    return new IlluminateReminderRepository(Sentinel::getUserRepository());
};

$container['workflow'] = function(Container $c) {
  return new App\Workflow\WorkflowEngine($c);
};

$container['validator'] = function () {
    return new App\Validation\Validator();
};

$container['mailer'] = function (Container $c) {
  $settings = $c->get('settings');
  return new App\Notification\EmailDispatcher($settings['email']);
};

$app->add(new \App\Middleware\ValidationErrorMiddleware($container));
$app->add(new \App\Middleware\OldInputMiddleware($container));
$app->add(new \App\Middleware\CsrfViewMiddleware($container));
//$app->add($container->csrf);

v::with('App\\Validation\\Rules');
