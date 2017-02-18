<?php

namespace App\Validation\Rules;

use \Respect\Validation\Rules\AbstractRule;
use \App\Models\EntradaProducto;

class FraccionValidation extends AbstractRule
{
    public function validate($input)
    {
      $partidas = EntradaProducto::
        where('entrada_id', '=', $input)
        ->where('fraccion_arancelaria', '=', NULL)
        ->orWhere('fraccion_arancelaria', '=', 0)
        ->count();
      return $partidas === 0;
    }
}
