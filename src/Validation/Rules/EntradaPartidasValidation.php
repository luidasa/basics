<?php

namespace App\Validation\Rules;

use \Respect\Validation\Rules\AbstractRule;
use \App\Models\Entrada;

class EntradaPartidasValidation extends AbstractRule
{
    public function validate($input)
    {
      $entrada = Entrada::with('partidas')
        ->find($input);
      return count($entrada->partidas) > 0;
    }
}
