<?php

namespace App\Validation\Rules;

use \Respect\Validation\Rules\AbstractRule;
use \Cartalyst\Sentinel\Native\Facades\Sentinel;

class EmailAvailable extends AbstractRule
{
    public function validate($input)
    {
      $credenciales=['email' => $input];
      $usuario = Sentinel::findUserByCredentials($credentials);
      return User::where('email', $input)->count() === 0;
    }
}
