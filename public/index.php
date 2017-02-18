<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (is_file($file)) {
        return false;
    }
}

session_start();

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../src/config.php';

require __DIR__ . '/../src/config.php';

if (file_exists(__DIR__ . '/../src/config_override.php')) {
    require __DIR__ . '/../src/config_override.php';
}

global $config;

$app = new \Slim\App($config);

// Set up dependencies container
require __DIR__ . '/../src/dependencies.php';

// Register routes
require __DIR__ . '/../app/routes.php';

$app->run();
