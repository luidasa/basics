<?php

$config['settings']['db'] =
  [
    'driver'        => 'mysql',
    'host'          => '192.185.74.25',
    'port'          => '3306',
    'database'      => 'admonwap_skyll',
    'username'      => 'admonwap_dev',
    'password'      => 'Xml0918lds',
    'charset'       => 'utf8',
    'collation'     => 'utf8_general_ci',
    'prefix'        => '',
  ];

$config['settings']['email'] =
  [
    'smtp_debug'          => 2, //Enable SMTP debugging // 0 = off (for production use) // 1 = client messages // 2 = client and server messages
    'smtp_servers'        => 'smtp.gmail.com',
    'enabled_smtp_auth'   => true,
    'smtp_username'       => 'visionwap@gmail.com',
    'smtp_password'       => 'Xml0918lds',
    'enabled_encryption'  => 'tls',                  // 'ssl'
    'tcp_port'            => 587,
    'email_remitente'     => 'visionwap@gmail.com',
    'name_remitente'      => 'Luis David Salazar',
    'email_reply'         => 'david@visionwap.com',
    'name_reply'          => 'Atención a Clientes VisionWap',
  ];

$config['settings']['workflow'] =
  [
    'filepath'            => '../private'   //Indica la ruta donde se van a almacenar los archivos del workflow.
  ];
