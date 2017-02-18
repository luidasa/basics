<?php


namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model as Model;

/**
 * Modelo de Archivos.
 */
class Tarea extends Model
{
  protected $fillable = ['estado', 'observaciones'];

  protected $table = "tareas";

  function estado() {
    return $this->hasOne('App\Workflow\Models\Estado', 'id', 'estado_id');
  }

  function proceso() {
    return $this->belongsTo('App\Workflow\Models\Proceso');
  }
}

 ?>
