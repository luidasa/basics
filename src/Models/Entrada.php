<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Orden de entrada.
 */
class Entrada extends Model {
  protected $table = "entradas";


  function __construct()
  {

  }

  public function cliente()
  {
      return $this->belongsTo('App\Models\Cliente');
  }

  public function proceso()
  {
      return $this->belongsTo('App\Workflow\Models\Proceso');
  }

  public function partidas() {
    return $this->hasMany('App\Models\EntradaProducto');
  }
}


 ?>
