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

    $this->get('/configuration', 'ConfigurationAction:edit')->setName('configuration');
})->add(new AutorizationMiddleware($container))
  ->add(new NotificationMiddleware($container))
  ->add(new AuthMiddleware($container));
