<?php


namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class Salida extends Model
{
  protected $table = "salidas";

  function __construct()
  {
    # code...
  }

  public function proceso()
  {
    return $this->belongsTo('App\Workflow\Models\Proceso');
  }

  public function destinatario() {
    return $this->belongsTo('App\Models\Destinatario');    
  }

  public function partidas() {
    return $this->hasMany('App\Models\SalidaProducto');
  }
}



 ?>
