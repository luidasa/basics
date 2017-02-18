<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

use App\Models\Salida;
use App\Models\SalidaProducto;
use App\Models\Producto;
use App\Models\Destinatario;

/**
* Manejo de cuentas de usuarios de usuarios.
*/
class ExtractionAction extends Action
{
  public function listing(Request $request, Response $response)
  {
    $this->logger->debug('List Extraction Order - Dispatched ');
    $partidas = SalidaProducto::with('salida',
      'salida.destinatario',
      'salida.proceso',
      'salida.proceso.tareaActual',
      'salida.proceso.tareaActual.estado')->get();

    return $this->view->render($response, 'extractions.twig', [
      'partidas' => $partidas
    ]);
  }

  public function add(Request $request, Response $response)
  {
    $this->logger->debug('Add Extraction Order - Dispatched ');
    $destinatarios = Destinatario::all();
    return $this->view->render($response, 'extraction.twig',
    [ 'destinatarios' => $destinatarios ]);
  }

  public function postAdd(Request $request, Response $response) {
    $this->logger->debug('Add Extraction Order - Processed ');

    // Verificamos si existe una orden de salida.
    $orden = Salida::with('proceso',
        'proceso.archivos',
        'partidas',
        'proceso.tareas',
        'proceso.tareas.estado',
        'proceso.tareaActual',
        'proceso.tareaActual.estado',
        'proceso.tareaActual.estado.transiciones')
      ->where('numero_pedimento', '=', $request->getParam('pedimento'))
      ->where('fecha_extraccion', '=', \Datetime::createFromFormat('d/m/Y', $request->getParam('fechaExtraccion'))->format('Y-m-d'))
      ->where('destinatario_id', '=', $request->getParam('destinatario'))
      ->first();

    //Si no existe la creamos, o si no se encuentra en captura.
    if (is_null($orden) || ($this->workflow->isFinished($orden->proceso))) {
      $orden = new Salida();
      $proceso = $this->workflow->start('Salidas', $request->getParam('observaciones'));
      $orden->destinatario_id = $request->getParam('destinatario');
      $orden->numero_pedimento= $request->getParam('pedimento');
      $consecutivo = Salida::where('numero_pedimento', '=', $request->getParam('pedimento')  )->count() + 1;
      Producto::where('numero_pedimento', '=', $request->getParam('pedimento'))
                ->update(['consecutivo' => $consecutivo]);
      $orden->consecutivo = $consecutivo;
      $orden->fecha_extraccion= \Datetime::createFromFormat('d/m/Y', $request->getParam('fechaExtraccion'));
      $orden->proceso_id      = $proceso->id;
    } else {
      $this->flash->addMessage('warning', 'Ya existe la orden de salida para ese destintario. Se agrega el producto la orden.');
      $proceso = $this->workflow->evaluation($orden->proceso, $request->getParam('operacion'), $request->getParam('observaciones'));
    }
    $files = $request->getUploadedFiles();
    if (!empty($files['archivo'])) {
      $newfile = $files['archivo'];
      $proceso = $this->workflow->attach($proceso, $newfile, $request->getParam('archivoDescripcion'));
    }
    $orden->save();

    $this->flash->addMessage('success', 'Hemos guardado la información de la orden de extracción.');
    return $response->withRedirect($this->router->pathFor('extraction.edit', ['id'=>$orden->id] ));
  }

/**
  Procesa la petición cuando hace el post para agregar un producto a la orden.
*/
  public function addProducto(Request $request, Response $response, $args) {
    $this->logger->debug('Add Producto to Extraction Order - Processed ');

    $producto = Producto::findOrFail($args['id']);
    $destinatarios = Destinatario::all();

    // Buscamos si existe una orden de extracción que tenga las caracterticas.
    $ordenes =  Salida::with('proceso',
        'proceso.archivos',
        'partidas',
        'proceso.tareas',
        'proceso.tareas.estado',
        'proceso.tareaActual',
        'proceso.tareaActual.estado',
        'proceso.tareaActual.estado.transiciones')
      ->where('numero_pedimento', '=', $producto->numero_pedimento)
      ->where('fecha_extraccion', '=', (new \Datetime())->format('Y-m-d'))
      ->get();

    // Le agregamos la cantidad y mostramos la forma.
    return $this->view->render($response, 'fastExtraction.twig',
    [
      'ordenes'       => $ordenes,
      'producto'      => $producto,
      'destinatarios' => $destinatarios
    ]);
  }

/**
  Procesa el post de la petición desde el inventario como si fuiera un fasttrack de
  agregar un nuevo producto.
*/
  public function postAddProducto(Request $request, Response $response, $args) {
    $this->logger->debug('Add Producto to Extraction Order - Processed ');

    $idCaja = $request->getParam();
    // Verificamos si podemos avanzar la tarea a al siguiente estado.
    $validation = $this->validator->validate($request, [
      'idPallets' => v::OneOrAnotherValidation($idCaja)->setName('Identificador de Pallet'),
      'idPallets' => v::optional(v::notEmpty()->alnum())->setName('Identificador de Pallet'),
      'idCajas'   => v::optional(v::notEmpty()->alnum())->setName('Identificador de caja')
    ]);
    if (!$validation->failed()) {
      $producto = Producto::findOrFail($args['id']);
      
      $orden = Salida::with('proceso',
          'proceso.archivos',
          'partidas',
          'proceso.tareas',
          'proceso.tareas.estado',
          'proceso.tareaActual',
          'proceso.tareaActual.estado',
          'proceso.tareaActual.estado.transiciones')
        ->find($request->getParam('orden'));

      // Buscamos si existe una orden de extracción para que agreguemos.
      if (is_null($orden)) {
        $orden = new Salida();
        $proceso = $this->workflow->start('Salidas', $request->getParam('observaciones'));
        $orden->destinatario_id = $request->getParam('destinatario');
        $orden->numero_pedimento= $request->getParam('pedimento');
        $consecutivo = Salida::where('numero_pedimento', '=', $request->getParam('pedimento')  )->count() + 1;
        Producto::where('numero_pedimento', '=', $request->getParam('pedimento'))
                  ->update(['consecutivo' => $consecutivo]);
        $orden->consecutivo = $consecutivo;
        $orden->fecha_extraccion= \Datetime::createFromFormat('d/m/Y', $request->getParam('fechaExtraccion'));
        $orden->proceso_id      = $proceso->id;
        $orden->save();
      } else {
        $proceso = $this->workflow->evaluation($orden->proceso, $request->getParam('operacion'), $request->getParam('observaciones'));
      }
      // Numero de partida
      $partida                  = new SalidaProducto();
      $partida->np              = $request->getParam('numeroParte');
      $partida->descripcion     = $request->getParam('descripcion');
      $partida->cantidad        = $request->getParam('cantidad');
      $partida->po              = $request->getParam('po');
      $partida->lote            = $request->getParam('lote');
      $partida->wsr             = $request->getParam('wsr');
      $partida->precio_unitario = $request->getParam('precioUnitario');
      $partida->precio_total    = $request->getParam('precioTotal');
      $partida->producto_id     = $request->getParam('producto_id');
      $partida->id_caja         = $request->getParam('id_caja');
      $partida->id_pallet       = $request->getParam('id_pallet');
      $orden->partidas()->save($partida);

      $this->flash->addMessage('success', 'Hemos guardado la información de la orden de extracción.');
    } else {
      $this->flash->addMessage('danger', 'Los datos que has introducido son incorrectos. Favor de corregir.');
    }
    return $response->withRedirect($this->router->pathFor('extraction.edit', ['id'=>$orden->id] ));
  }

  public function edit(Request $request, Response $response, $args)
  {
    $this->logger->debug('Edit Extraction Order - Dispatched ');
    $destinatarios = Destinatario::all();

    $orden        = Salida::with('proceso',
    'proceso.archivos',
    'partidas',
    'destinatario',
    'proceso.tareas',
    'proceso.tareas.estado',
    'proceso.tareaActual',
    'proceso.tareaActual.estado',
    'proceso.tareaActual.estado.transiciones')->findOrFail($args['id']);

    return $this->view->render($response, 'extraction.twig',
    [
      'orden'         => $orden,
      'destinatarios' => $destinatarios
    ]);
  }

/**
  Se guarda el post del edit.
*/
  public function postEdit(Request $request, Response $response, $args) {
    $this->logger->debug('Edit Extraction Order - Processed ');

    $orden        = Salida::with('proceso',
      'proceso.archivos',
      'partidas',
      'partidas.producto',
      'proceso.tareas',
      'proceso.tareas.estado',
      'proceso.tareaActual',
      'proceso.tareaActual.estado',
      'proceso.tareaActual.estado.transiciones')->findOrFail($args['id']);
    $proceso = $orden->proceso;
    $orden->destinatario_id = $request->getParam('destinatario');
    $orden->numero_pedimento= $request->getParam('pedimento');
    $orden->fecha_extraccion= \Datetime::createFromFormat('d/m/Y', $request->getParam('fechaExtraccion'));
    $orden->proceso_id      = $proceso->id;
    $orden->save();

    // Cargamos los archivos si es que tiene alguno....
    $files = $request->getUploadedFiles();
    if (!empty($files['archivo'])) {
      $newfile = $files['archivo'];
      $proceso = $this->workflow->attach($proceso, $newfile, $request->getParam('archivoDescripcion'));
    }

    // hacemos la transicion en base a la operación.
    $proceso = $this->workflow->evaluation($proceso, $request->getParam('operacion'), $request->getParam('observaciones'));

    // Si es un estado terminal entonces agregamos el producto al inventario.
    if ($this->workflow->isFinished($proceso)) {
      foreach($orden->partidas as $partida) {
        // Le restamos la cantidad de productos al inventario.
        $producto = $partida->producto;
        $producto->cantidad_actual = $producto->cantidad_actual - $partida->cantidad;
        $producto->consecutivo += 1;
        $producto->save();
        $this->logger->debug('Restando productos del Inventario Progress Extraction Order - Processed ');
      }
    }

    $this->flash->addMessage('success', 'Hemos guardado la información de la orden de extracción.');
    return $response->withRedirect($this->router->pathFor('extraction.edit', ['id'=>$orden->id] ));
  }

  /**
    Devuelve un archivo leido.
  */
  public function showFile(Request $request, Response $response, $args)
  {
    $archivo = $this->workflow->getArchivoById($args['id']);
    $response = $response->withHeader('Content-Description', 'File Transfer')
       ->withHeader('Content-Type', 'application/octet-stream')
       ->withHeader('Content-Disposition', 'attachment;filename="'. basename( $archivo->archivo ) .'"')
       ->withHeader('Expires', '0')
       ->withHeader('Cache-Control', 'must-revalidate')
       ->withHeader('Pragma', 'public')
       ->withHeader('Content-Length', filesize($archivo->archivo));

    readfile($archivo->archivo);
    return $response;
  }

  /**
    Borra un archivo.
  */
  public function trashFile(Request $request, Response $response, $args)
  {
    $this->logger->debug('Delete File Order - Processed');
    $archivo = $this->workflow->getArchivoById($args['id'])->delete();
    $this->flash->addMessage('success', 'Hemos borrado el archivo del proceso.');
    return $response->withRedirect($this->router->pathFor('extraction.edit', ['id'=>$args['orden_id']] ));
  }

  public function delete(Request $request, Response $response)
  {
    return $this->view->render($response, 'extraction.twig');
  }

  public function bol(Request $request, Response $response, $args)
  {
    $objReader = \PHPExcel_IOFactory::createReader('Excel5');
    $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/bol.xls");

    $orden = SALIDA::with(
      'partidas',
      'destinatario',
      'partidas.producto')
    ->findOrFail($args['id']);
    $objPHPExcel->getActiveSheet()->setCellValue('C7', $orden->destinatario->nombre);
    $objPHPExcel->getActiveSheet()->setCellValue('C8', $orden->destinatario->domicilio);
    $objPHPExcel->getActiveSheet()->setCellValue('C9', $orden->destinatario->localidad);
    $objPHPExcel->getActiveSheet()->setCellValue('A14', $orden->numero_pedimento);
    $objPHPExcel->getActiveSheet()->setCellValue('E14', $orden->consecutivo);
    $objPHPExcel->getActiveSheet()->setCellValue('R3', date('d/m/Y'));
    $objPHPExcel->getActiveSheet()->setCellValue('U3', $orden->id); //Order
    $rowBase = $row = 18;
    foreach ($orden->partidas as $partida) {
      $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A' . $row, $partida->cantidad)
        ->setCellValue('C' . $row, $partida->cantidad)
        ->setCellValue('E' . $row, $partida->descripcion)
        ->setCellValue('E' . ($row + 1), "P/N")
        ->setCellValue('H' . ($row + 1), $partida->np)
        ->setCellValue('K' . $row, $partida->peso_neto)
        ->setCellValue('M' . $row, $partida->peso_neto * $partida->cantidad)
        ->setCellValue('P' . $row, $partida->lote . '/' . $partida->id_pallet . ',' . $partida->id_caja)
        ->setCellValue('T' . $row, $partida->consecutivo)
        ->setCellValue('V' . $row, $partida->po);
        $row += 2;
    }

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $file = 'php://output';
    $objWriter->save($file);

    return $response->withHeader('Content-Type',  'application/vnd.ms-excel')
                  ->withHeader('Content-Disposition', 'attachment;filename=bol.xls')
                  ->withHeader('Content-Type', 'application/octet-stream')
                  ->withHeader('Content-Type', 'application/download')
                  ->withHeader('Content-Description', 'File Transfer')
                  ->withHeader('Content-Transfer-Encoding', 'binary')
                  ->withHeader('Expires', '0')
                  ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                  ->withHeader('Pragma', 'public');
  }

  public function argo(Request $request, Response $response, $args)
  {
    $objReader = \PHPExcel_IOFactory::createReader('Excel5');
    $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/argo.xls");

    $orden = SALIDA::with('partidas', 'partidas.producto')
      ->findOrFail($args['id']);

    $objPHPExcel->getActiveSheet()->setCellValue('K5', date('d/m/Y'));
    $objPHPExcel->getActiveSheet()->setCellValue('K14', $orden->destinatario->nombre);
    // $objPHPExcel->getActiveSheet()->setCellValue('C8', $orden->destinatario.domicilio);
    // $objPHPExcel->getActiveSheet()->setCellValue('C9', $orden->destinatario.localidad);
    $objPHPExcel->getActiveSheet()->setCellValue('G19', $orden->id);
    $objPHPExcel->getActiveSheet()->setCellValue('G18', $orden->numero_pedimento);
    $rowBase = $row = 26;
    foreach ($orden->partidas as $partida) {
      $objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A' . $row, $partida->cantidad)
        ->setCellValue('C' . $row, $partida->np)
        ->setCellValue('F' . $row, $partida->descripcion)
        ->setCellValue('K' . $row, $partida->lote . '/' . $partida->id_pallet . ',' . $partida->id_caja)
        ->setCellValue('N' . $row, $partida->precio_unitario);
        $row += 1;
    }

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $file = 'php://output';
    $objWriter->save($file);

    return $response->withHeader('Content-Type', 'application/vnd.ms-excel')
                  ->withHeader('Content-Disposition', 'attachment;filename=argo.xls')
                  ->withHeader('Content-Type', 'application/octet-stream')
                  ->withHeader('Content-Type', 'application/download')
                  ->withHeader('Content-Description', 'File Transfer')
                  ->withHeader('Content-Transfer-Encoding', 'binary')
                  ->withHeader('Expires', '0')
                  ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                  ->withHeader('Pragma', 'public');
  }

  public function addItem(Request $request, Response $response, $args)
  {
    $this->logger->debug('Add Item to Extraction Order - Dispatched');
    $orden        = SALIDA::findOrFail($args['orden_id']);
    return $this->view->render($response, 'itemExtraction.twig', ['orden'=>$orden]);
  }

  public function postAddItem(Request $request, Response $response, $args)
  {
    $this->logger->debug('Add Item to Extraction Order - Processed');
    $url          = $this->router->pathFor('extraction.edit', ['id'=>$args['orden_id']] );

    $idCaja = $request->getParam();
    // Verificamos si podemos avanzar la tarea a al siguiente estado.
    $validation = $this->validator->validate($request, [
      'idPallets' => v::OneOrAnotherValidation($idCaja)->setName('Identificador de Pallet'),
      'idPallets' => v::optional(v::notEmpty()->alnum())->setName('Identificador de Pallet'),
      'idCajas'   => v::optional(v::notEmpty()->alnum())->setName('Identificador de caja')
    ]);
    if (!$validation->failed()) {
      $orden        = Salida::findOrFail($args['orden_id']);
      $partida      = new SalidaProducto();
      $partida->np              = $request->getParam('numeroParte');
      $partida->descripcion     = $request->getParam('descripcion');
      $partida->cantidad        = $request->getParam('cantidad');
      $partida->po              = $request->getParam('po');
      $partida->lote            = $request->getParam('lote');
      $partida->precio_unitario = $request->getParam('precioUnitario');
      $partida->producto_id     = $request->getParam('producto_id');
      $partida->id_caja         = $request->getParam('idCajas');
      $partida->id_pallet       = $request->getParam('idPallets');

      $orden->partidas()->save($partida);

      if ($request->getParam('operacion') == 'Nuevo') {
        $url          = $this->router->pathFor('extraction.item.add', ['orden_id'=>$orden->id] );
      }
      $this->flash->addMessage('success', 'Agregamos una nueva partida a tu orden de extracción.');
    } else {
      $this->flash->addMessage('danger', 'Los datos que capturados son incorrectos.');
    }
    return $response->withRedirect($url);
  }

  public function editItem(Request $request, Response $response, $args)
  {
    $this->logger->debug('Edit Item to Extraction Order - Dispatched');
    $orden        = Salida::with('partidas', 'partidas.producto')->findOrFail($args['orden_id']);
    $partida      = $orden
                      ->partidas()
                      ->where('id', "=", $args['id'])->first();
    return $this->view->render($response, 'itemExtraction.twig',
      [
        'orden'   => $orden,
        'partida' => $partida
      ]);
  }

  public function postEditItem(Request $request, Response $response, $args)
  {
    $this->logger->debug('Edit Item to Extraction Order - Processed');
    $url = $this->router->pathFor('extraction.item.edit', ['orden_id'=>$args['orden_id'], 'id'=>$args['id']]);
    $this->logger->debug($request->getParam('idCajas') . ' - ' . $request->getParam('idPallets'));
    $idCaja = $request->getParam('idCajas');
    // Verificamos si podemos avanzar la tarea a al siguiente estado.
    $validation = $this->validator->validate($request, [
      'idPallets' => v::OneOrAnotherValidation($idCaja)->setName('Locación no definida'),
      'idPallets' => v::notEmpty()->alnum()->setName('Identificador de pallet incorrecto'),
      'idCajas'   => v::notEmpty()->alnum()->setName('Identificador de caja incorrecto')
    ]);
    if (!$validation->failed()) {
      $partida      = SALIDAPRODUCTO::with('salida', 'producto')->findOrFail($args['id']);
      $url          = $this->router->pathFor('extraction.edit', ['id'=>$partida->salida->id] );
      $partida->np              = $request->getParam('numeroParte');
      $partida->descripcion     = $request->getParam('descripcion');
      $partida->cantidad        = $request->getParam('cantidad');
      $partida->po              = $request->getParam('po');
      $partida->lote            = $request->getParam('lote');
      $partida->precio_unitario = $request->getParam('precioUnitario');
      $partida->producto_id     = $request->getParam('producto_id');
      $partida->id_caja         = $request->getParam('idCajas');
      $partida->id_pallet       = $request->getParam('idPallets');
      $partida->save();
      if ($request->getParam('operacion') == 'Nuevo') {
        $url = $this->router->pathFor('extraction.item.add', ['orden_id'=>$partida->salida->id,'id'=>$partida->id]);
      }
      $this->flash->addMessage('success', 'Actualizamos la información de la partida de la orden de extracción.');
    } else {
      $this->flash->addMessage('danger', 'Actualizamos la información de la partida de la orden de extracción.');
    }
    return $response->withRedirect($url);
  }

  public function deleteItem(Request $request, Response $response, $args)
  {
    $this->logger->debug('Edit Item to Extraction Order - Processed');

    $partida      = SALIDAPRODUCTO::with('salida', 'producto')->findOrFail($args['id']);
    $orden_id     = $partida->salida->id;
    $partida->delete();
    $this->flash->addMessage('success', 'Quitamos la partida de la orden de extracción.');
    return $response->withRedirect($this->router->pathFor('extraction.edit', ['id'=>$orden_id] ));
  }
}
