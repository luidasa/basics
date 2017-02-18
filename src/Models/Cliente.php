<?php


namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class Cliente extends Model
{
  protected $table = "clientes";

  function __construct()
  {
  }

  public function entradas() {
    return $this->hasMany('App\Models\Entrada');
  }
}



 ?>
