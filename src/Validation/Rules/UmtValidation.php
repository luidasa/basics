<?php

namespace App\Validation\Rules;

use \Respect\Validation\Rules\AbstractRule;
use \App\Models\EntradaProducto;

class UmtValidation extends AbstractRule
{
    public function validate($input)
    {
      $partidas = EntradaProducto::
        where('entrada_id', '=', $input)
        ->where('umt', '=', NULL)
        ->orWhere('umt', '=', '')
        ->count();
      return $partidas === 0;
    }
}
