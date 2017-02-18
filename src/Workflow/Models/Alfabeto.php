<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
  Define un nuevo modelo.
 */
class Alfabeto extends Model
{
  protected $fillable = ['nombre', 'descripcion'];

  protected $table = "alfabetos";

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
}



 ?>
