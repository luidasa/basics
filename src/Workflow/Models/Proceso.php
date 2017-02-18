<?php

namespace App\Workflow\Models;

use \Illuminate\Database\Eloquent\Model;

/**
 * Modelo de Archivos.
 */
class Proceso extends Model
{
  protected $fillable  = ['nombre', 'descripcion', 'fecha_alta'];

  protected $table    = "procesos";

  function __construct()
  {
  }

  public function tareaActual() {
    return $this->hasOne('App\Workflow\Models\Tarea', 'id', 'tareaActual_id');
  }

  public function automata() {
    return $this->hasOne('App\Workflow\Models\Automata', 'id', 'automata_id');
  }

  public function tareas()  {
    return $this->hasMany('App\Workflow\Models\Tarea');
  }

  public function archivos() {
    return $this->hasMany('App\Workflow\Models\Archivo');
  }
}

 ?>
