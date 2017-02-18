<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class Archivo extends Model
{
  protected $table = "archivos";

  function __construct()
  {
    # code...
  }

  function proceso() {
    return $this->belongsTo('\App\Workflow\Models\Proceso');
  }
}



 ?>
