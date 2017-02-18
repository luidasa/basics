<?php

namespace App\Workflow;

use App\Workflow\Models\Automata;

/**
 * Fabrica para obtener las definiciones de cada proceso.
 * Debe de cargar en base a algunos de los datos que estan
 */
class FabricaAutomata
{
  function __construct()
  {
  }

/**
  Carga la definición de una definición del proceso y la devuelve para que sea
  procesada por el WorflowEngine.
*/
  public static function load($nombreProceso) {

    $def = Automata::where('nombre', '=', $nombreProceso)->get();
    var_dump($def);
    return $def;

  }
}



 ?>
