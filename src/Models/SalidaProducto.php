<?php


namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class SalidaProducto extends Model
{
  protected $table = "salida_producto";

  function __construct()
  {
    # code...
  }

  public function salida()
  {
      return $this->belongsTo('App\Models\Salida');
  }

  public function producto() {
    return $this->belongsTo('App\Models\Producto');
  }

}



 ?>
