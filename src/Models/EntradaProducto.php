<?php


namespace App\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Partidas de una Entrada.
 */
class EntradaProducto extends Model
{
  protected $table = "entrada_producto";

  function __construct()
  {
    # code...
  }

  public function entrada()
  {
    return $this->belongsTo('App\Models\Entrada');
  }

  public function producto() {
    return $this->belongsTo('App\Models\Producto');
  }
}




 ?>
