<?php

$config = [
    'settings' => [
        // Slim settings
        //'addContentLengthHeader = false,
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,
        'debug' => true,
        'whoops.editor' => 'sublime',

        // View settings
        'view' => [
            'template_path' => __DIR__.'/../resources/views',
            'twig' => [
                // TODO cache should be false and override from the config_override.php
                'cache' => __DIR__.'/../cache/twig',
                'debug' => true,
                'auto_reload' => true,
            ],
        ],

        // monolog settings
        'logger' => [
            'name' => 'app',
            'path' => __DIR__.'/../log/app.log',
        ],

        // Empty DB configuration.
        'db' => [
            'driver' => '',
            'host' => '',
            'database' => '',
            'username' => '',
            'password' => '',
            'charset' => '',
            'collation' => '',
            'prefix' => '',
        ],

        'email' => [
          'smtp_debug'          => 2, //Enable SMTP debugging // 0 = off (for production use) // 1 = client messages // 2 = client and server messages
          'smtp_servers'        =>'',
          'enabled_smtp_auth'   =>true,
          'smtp_username'       =>'',
          'smtp_password'       =>'',
          'enabled_encryption'  =>'',                  // 'tls', 'ssl' or ''
          'tcp_port'            =>587,
          'email_remitente'     =>'',
          'name_remitente'      =>'',
          'email_replay'        =>'',
          'name_replay'         =>'',
        ],

    ],
];
