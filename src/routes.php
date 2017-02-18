<?php

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\AutorizationMiddleware;
use App\Middleware\NotificationMiddleware;

// Routes
$app->get('/about', 'AboutAction:aboutPage')->setName('about');
$app->get('/privacy', 'AboutAction:privacyPage')->setName('privacy');
$app->get('/terms', 'AboutAction:termsPage')->setName('terms');

$app->get('/contact', 'ContactAction:form')->setName('contact');
$app->post('/contact', 'ContactAction:contactar');

$app->get('/search', 'SearchAction:searchResults')->setName('search');
$app->post('/search', 'SearchAction:postSearch');

$app->get('/start', 'ConfigurationAction:start')->setName('start');

$app->group('', function () {

    /* Muestra y atiende la forma de registro de usuario. */
    $this->get('/auth/signup/{id}/{code}', 'AuthAction:activateSignUp')->setName('auth.signup.activate');
    $this->get('/auth/signup', 'AuthAction:getSignUp')->setName('auth.signup');
    $this->post('/auth/signup', 'AuthAction:postSignUp');

    /* Muestra y atiende de recuperación de password */
    $this->get('/auth/password/change', 'AuthAction:getRecoverPassword')->setName('auth.password.change');
    $this->post('/auth/password/change', 'AuthAction:postRecoverPassword');
    $this->get('/auth/password/change/{id}/{code}', 'AuthAction:updatePasswordView')->setName('auth.password.update');
    $this->post('/auth/password/change/{id}/{code}', 'AuthAction:updatePassword');

    /* Muestra y atiende la forma de inicio de sesión. */
    $this->get('/auth/signin', 'AuthAction:getSignIn')->setName('auth.signin');
    $this->post('/auth/signin', 'AuthAction:postSignIn');

})->add(new GuestMiddleware($container));

$app->group('', function () {
    $this->get('/', 'HomeAction:index')->setName('home');
    $this->get('/auth/signout', 'AuthAction:signOut')->setName('auth.signout');
    $this->get('/auth/settings', 'AuthAction:showSettings')->setName('auth.settings');
    $this->get('/auth', 'AuthAction:showProfile')->setName('auth.profile');
    $this->post('/auth', 'AuthAction:updateProfile');

})->add(new NotificationMiddleware($container))
  ->add(new AuthMiddleware($container));

$app->group('', function () {
    $this->get('/account/list', 'AccountAction:listing')->setName('accounts');
    $this->get('/account/add', 'AccountAction:add')->setName('account.add');
    $this->get('/account/delete/{id}', 'AccountAction:delete')->setName('account.delete');
    $this->get('/account/{id}', 'AccountAction:edit')->setName('account.edit');

    $this->post('/account/add', 'AccountAction:postAdd');
    $this->post('/account/{id}', 'AccountAction:postEdit');

    $this->get('/registration/list', 'RegistrationOrderAction:listing')->setName('registrations');
    $this->get('/registration/receipt/{id}', 'RegistrationOrderAction:receipt')->setName('registration.receipt');
    $this->get('/registration/packing/{id}', 'RegistrationOrderAction:packing')->setName('registration.packing');
    $this->get('/registration/invoice/{id}', 'RegistrationOrderAction:invoice')->setName('registration.invoice');
    $this->get('/registration/add', 'RegistrationOrderAction:add')->setName('registration.add');
    $this->get('/registration/delete/{id}', 'RegistrationOrderAction:delete')->setName('registration.delete');
    $this->get('/registration/drop/file/{orden_id}/{id}', 'RegistrationOrderAction:trashFile')->setName('registration.file.trash');
    $this->get('/registration/show/file/{id}', 'RegistrationOrderAction:showFile')->setName('registration.file');
    $this->get('/registration/{id}', 'RegistrationOrderAction:edit')->setName('registration.edit');
    $this->get('/registration/item/add/{orden_id}', 'RegistrationOrderAction:addItem')->setName('registration.item.add');
    $this->get('/registration/item/delete/{partida_id}', 'RegistrationOrderAction:deleteItem')->setName('registration.item.delete');
    $this->get('/registration/item/{orden_id}/{partida_id}', 'RegistrationOrderAction:editItem')->setName('registration.item.edit');

    $this->post('/registration/add', 'RegistrationOrderAction:postAdd');
    $this->post('/registration/inprogress/{id}', 'RegistrationOrderAction:postEditEnProgreso')->setName('registration.edit.inprogress');
    $this->post('/registration/item/add/{orden_id}', 'RegistrationOrderAction:postAddItem');
    $this->post('/registration/item/{orden_id}/{partida_id}', 'RegistrationOrderAction:postEditItem');
    $this->post('/registration/{id}', 'RegistrationOrderAction:postEdit');

    $this->get('/extraction/list', 'ExtractionAction:listing')->setName('extractions');
    $this->get('/extraction/export', 'ExtractionAction:listing')->setName('extractions.export');
    $this->get('/extraction/add', 'ExtractionAction:add')->setName('extraction.add');
    $this->get('/extraction/delete/{id}', 'ExtractionAction:delete')->setName('extraction.delete');
    $this->get('/extraction/argo/{id}', 'ExtractionAction:argo')->setName('extraction.argo');
    $this->get('/extraction/bol/{id}', 'ExtractionAction:bol')->setName('extraction.bol');
    $this->get('/extraction/drop/file/{orden_id}/{id}', 'ExtractionAction:trashFile')->setName('extraction.file.trash');
    $this->get('/extraction/show/file/{id}', 'ExtractionAction:showFile')->setName('extraction.file');
    $this->get('/extraction/{id}', 'ExtractionAction:edit')->setName('extraction.edit');
    $this->get('/extraction/add/product/{id}', 'ExtractionAction:addProducto')->setName('extraction.product.add');
    $this->get('/extraction/item/add/{orden_id}', 'ExtractionAction:addItem')->setName('extraction.item.add');
    $this->get('/extraction/item/delete/{id}', 'ExtractionAction:deleteItem')->setName('extraction.item.delete');
    $this->get('/extraction/item/{orden_id}/{id}', 'ExtractionAction:editItem')->setName('extraction.item.edit');

    $this->post('/extraction/add', 'ExtractionAction:postAdd');
    $this->post('/extraction/{id}', 'ExtractionAction:postEdit');
    $this->post('/extraction/item/add/{orden_id}', 'ExtractionAction:postAddItem');
    $this->post('/extraction/item/{orden_id}/{id}', 'ExtractionAction:postEditItem');
    $this->post('/extraction/add/product/{id}', 'ExtractionAction:postAddProducto');

    $this->get('/shipment/searchByNP/{numero_pedimento}', 'ShipmentAction:searchByNP')->setName('shipments.searchByNP');
    $this->get('/shipment/search/{id}', 'ShipmentAction:search')->setName('shipments.search');
    $this->get('/shipment/list', 'ShipmentAction:listing')->setName('shipments');
    $this->get('/shipment/export', 'ShipmentAction:export')->setName('shipments.export');

    $this->get('/configuration', 'ConfigurationAction:edit')->setName('configuration');
})->add(new AutorizationMiddleware($container))
  ->add(new NotificationMiddleware($container))
  ->add(new AuthMiddleware($container));
