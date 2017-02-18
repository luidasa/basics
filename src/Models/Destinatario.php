<?php


namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class Destinatario extends Model
{
  protected $table = "destinatarios";

  function __construct()
  {
  }

  public function salidas() {
    return $this->hasMany('App\Models\Salida');
  }
}



 ?>
