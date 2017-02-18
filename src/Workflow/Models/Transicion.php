<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
  Define las transiciones que puede tener un flujo de trabajo.

 */
class Transicion extends Model
{
  protected $table = "transiciones";

  function __construct()
  {
    # code...
  }

/**
  Devuelve la definición del proceso al cual pertenece.
*/
  public function proceso() {

    return $this->belongsTo('App\Workflow\Models\Automata');
  }

/**
  Devuelve el estado origen del proceso al cual pertenece.
*/
  public function origen() {
    return $this->hasOne('App\Workflow\Models\Estado', 'id', 'origen_id');
  }

/**
  Devuelve el estado final despues de que recibe el token.
*/
  public function destino() {
    return $this->hasOne('App\Workflow\Models\Estado', 'id', 'destino_id');
  }

/**
  Token que debe de encontrarse para cambiar de estado.
*/
  public function token() {
    return $this->hasOne('App\Workflow\Models\Alfabeto', 'id', 'token_id');
  }

}


 ?>
