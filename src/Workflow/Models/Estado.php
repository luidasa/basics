<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 Representa la definición de un estado que define un workflow.
 */
class Estado  extends Model
{
  protected $fillable = ['nombre', 'descripcion'];

  protected $table = "estados";

  function __construct()
  {
    # code...
  }

  /**
    Devuelve a que proceso pertenece
  */
  public function proceso () {

    return $this->belongsTo('App\Workflow\Models\Automata');
  }

/**
  Devuelve las operaciones que pueden realizarse en este estado.
*/
  public function transiciones() {
    return $this->hasMany('App\Workflow\Models\Transicion', 'origen_id', 'id');
  }
}


 ?>
