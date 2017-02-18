<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 *
 */
class Automata extends Model {

  protected $table = "automatas";

  /**
    Carga la versión apropiada de la definición del proceso, para que el
    WorkflowEngine pueda procesar los datos.
  */
  public function __construct() {

  }

  public function estadoInicial() {
    return $this->hasOne('App\Workflow\Models\Estado', 'id', 'estadoInicial_id');
  }

/**
  Devuelve la definición de estados que pertenecen a un proceso.
*/
  public function estados() {

    return $this->hasMany('App\Workflow\Models\Estado');
  }

  /**
    Devuelve la definición de estados que pertenecen a un proceso.
  */
    public function alfabeto() {

      return $this->hasMany('App\Workflow\Models\Alfabeto');
    }

/**
  Devuelve la definición de las transiciones que pertenecen a un proceso.
*/
  public function transiciones() {

    return $this->hasMany('App\Workflow\Models\Transicion');
  }

}

 ?>
