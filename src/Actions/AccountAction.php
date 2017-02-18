<?php

namespace VisionWap\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use Cartalyst\Sentinel\Native\Facades\Sentinel;

/**
 * Manejo de cuentas de usuarios de usuarios.
 */
class AccountAction extends Action
{
    /**
     * Muestra la forma para cambiar los datos del profile desde la cuenta.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function profile(Request $request, Response $response)
    {
        $this->view->getEnvironment()->addGlobal('user', $user);
        return $this->view->render($response, 'profile.twig');
    }

    /**
     * Actualiza los datos del usuario desde la vista de cuenta.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function updateProfile(Request $request, Response $response)
    {
        return $response->withRedirect($this->router->pathFor('account'));
    }

    /**
     * Muestra el formulario para cambio del password del usuario desde la vista de cuenta.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function getChangePassword(Request $request, Response $response)
    {
        return $this->view->render($response, 'password.twig');
    }

    /**
     * Muestra el formulario para cambio del password del usuario desde la vista de cuenta.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     */
    public function postChangePassword(Request $request, Response $response)
    {
    }

    public function listing(Request $request, Response $response)
    {
      $this->logger->info('User List Page Dispatched');
      $users = Sentinel::getUserRepository()->all();
      return $this->view->render($response, 'users.twig', ['usuarios' => $users]);
    }

    public function add(Request $request, Response $response)
    {
      $this->logger->info('Add User Page Dispatched');
      return $this->view->render($response, 'user.twig');
    }

    public function postAdd(Request $request, Response $response) {
      $credentials = [
        'first_name'=>$request->getParam('nombre'),
        'last_name'=>$request->getParam('apellidos'),
        'email'=>$request->getParam('email'),
        'password'=>$request->getParam('password')
      ];
      $user = Sentinel::registerAndActivate($credentials);
      $role = Sentinel::findRoleByName($request->getParam('rol'));
      $role->users()->attach($user);
      $url = $this->router->pathFor('account.edit', ["id"=>$user->id]);
      $this->flash->addMessage('success', 'Se agrego un nuevo usuario al catalogo.');
      return $response->withRedirect($url);
    }

    public function edit(Request $request, Response $response, $args)
    {
      $this->logger->info('Edit Page Dispatched');
      $id = $args['id'];
      $user = Sentinel::findUserById($id);
      $userRoles = Sentinel::findUserById($id)->roles()->get();
      return $this->view->render($response, 'user.twig',
      [
        'usuario'=>$user,
        'rol'=>$userRoles[0]
      ]);
    }

    public function postEdit(Request $request, Response $response, $args)
    {
      $this->logger->info('Edit Page Processed');
      $id = $args['id'];
      $user = Sentinel::findUserById($id);
      $userRoles = Sentinel::findUserById($id)->roles()->get();
      $credentials = [
        'first_name'=>$request->getParam('nombre'),
        'last_name'=>$request->getParam('apellidos'),
        'email'=>$request->getParam('email'),
        'password'=>$request->getParam('password')
      ];
      Sentinel::update($user, $credentials);
      foreach ($roles as $key => $value) {
          $roleUser = Sentinel::findRoleByName($value);
          $roleUser->users()->detach($user);
          if ($key == $role) {
              $roleUser->users()->attach($user);
          }
      }
      $this->flash->addMessage('success', 'Los datos del usuario se han actualizado');
      return $response->withRedirect($this->router->pathFor('account.edit', ['id'=>$id]));
    }

    public function delete(Request $request, Response $response, $args)
    {
      $this->logger->info('Delete User Processed');
      $id = $args['id'];
      var_dump($id);
      Sentinel::findUserById($id)->delete();
      $this->flash->addMessage('warning', 'Los datos del usuario se han borrado');
      return $response->withRedirect($this->router->pathFor('accounts'));
    }
}
