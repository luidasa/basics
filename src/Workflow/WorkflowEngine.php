<?php

namespace App\Workflow;

use App\Workflow\Models\Automata;
use App\Workflow\Models\Transicion;
use App\Workflow\Models\Alfabeto;
use App\Workflow\Models\Proceso;
use App\Workflow\Models\Tarea;
use App\Workflow\Models\Archivo;
use \Cartalyst\Sentinel\Native\Facades\Sentinel;

/**
 *
 */
class WorkflowEngine
{
  private $container;
  private $fileSystem;
  private $use;

  function __construct($c)
  {
    $this->container = $c;
    $settings = $c->get('settings');
    $this->fileSystem = $settings['workflow']['filepath'];
    $this->user = Sentinel::getUser();
  }

/**
  Crea una instancia del proceso un proceso y almacena la información adicional
  Obtiene la definición del automata e inicia el proceso creando la primera
  tarea del mismo.
  Las tareas pueden ser de varios tipos.
*/
  function start($itemName, $observaciones = "Se inicia un nuevo proceso") {

    $definicion = Automata::where('nombre', '=',$itemName)->first();

    $proceso = new Proceso();
    $proceso->nombre        = $definicion->nombre;
    $proceso->descripcion   = $definicion->descripcion;
    $proceso->fecha_alta    = new \Datetime();
    $proceso->automata_id   = $definicion->id;
    $proceso->save();

    $task = new Tarea();
    $task->estado_id = $definicion->estadoInicial->id;
    $task->observaciones = $observaciones;
    $task->user_id = $this->user->id;
    $proceso->tareas()->save($task);

    $proceso->tareaActual_id = $task->id;
    $proceso->save();
    return $proceso;
  }

  /**
    Devuelve la tarea por default que debe de hacer el usuario.
  */
  function getTarea($item) {

    $proceso = $item->proceso()->first()->tareaActual();
  }


/**
  Obtiene el archivo por Id
*/
  function getArchivoById($id) {

    return Archivo::find($id);

  }

/**
  Agrega un nuevo archivo al proceso.
*/
  function attach($proceso, $newfile, $descripcion) {

    if ($newfile->getError() === UPLOAD_ERR_OK) {
      $uploadFileName       = $newfile->getClientFilename();
      $newFileName          = $this->fileSystem . '/' . $proceso->id . '-' . $uploadFileName;
      $newfile->moveTo($newFileName);
      $archivo              = new Archivo();
      $archivo->archivo     = $newFileName;
      $archivo->descripcion = $descripcion;
      $proceso->archivos()->save($archivo);
    }
    return $proceso;
  }


/**
  Termina una tarea y busca avanzar a la siguiente tarea definida en la transicion del automata.
*/
  function evaluation($proceso, $token, $observaciones = "Transicion por default") {

    $this->container->logger->debug("Evaluando al proceso para disparar la transición.");

    // Obtenemos la tarea actual que vamos a cerrar
    $tareaActual = $proceso->tareaActual()->with('estado')->first();
    $estadoActual = $tareaActual->estado;

    $transicion = Transicion::with('origen','destino', 'token')
      ->where([
        ['origen_id', '=', $estadoActual->id],
        ['alfabetos.nombre', '=', $token]])
      ->join('alfabetos', 'alfabetos.id', '=', 'token_id')
      ->first();
    $estadoSiguiente = $transicion->destino;
    if (isset($estadoSiguiente)) {
      $tareaActual->fecha_cierre = new \Datetime();
      $tareaActual->save();

      $task = new Tarea();
      $task->estado_id = $estadoSiguiente->id;
      $task->observaciones = $observaciones;
      $task->user_id = $this->user->id;
      $proceso->tareas()->save($task);

      $proceso->tareaActual_id = $task->id;
      $proceso->save();
    }
    return $proceso;
  }

  function isFinished($proceso) {

    $this->container->logger->debug("Evaluando al proceso se encuentra en un estado final. " . $proceso->tareaActual->estado->nombre);

    // Obtenemos la tarea en el cual se encuentra el proceso, y verificamos si tiene definidas transiciones.
    $tareaActual = $proceso->tareaActual()->with('estado', 'estado.transiciones')->first();
    $resultado = $tareaActual->estado->transiciones()->count() == 0;
    $this->container->logger->debug("Es un estado final $resultado");
    return $resultado;
  }
}


 ?>
