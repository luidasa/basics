<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Capsule\Manager as DB;

use \App\Models\Entrada;
use \App\Models\EntradaProducto;
use \App\Models\Producto;
use \App\Models\Cliente;

use \App\Workflow\Models\Proceso;
use \App\Workflow\Models\Tarea;
use \App\Workflow\Models\Automata;


/**
* Manejo de las ordenes de Registro. Importación de embarques.
*/
class RegistrationOrderAction extends Action
{

  public function listing(Request $request, Response $response)
  {
    $this->logger->debug('List Registration Order - Dispatched ');
    $ordenes = DB::table('entradas')
            ->join('procesos', 'entradas.proceso_id', '=', 'procesos.id')
            ->join('tareas', 'procesos.tareaActual_id', '=', 'tareas.id')
            ->join('estados', 'estados.id', '=', 'tareas.estado_id')
            ->select('entradas.*', 'estados.nombre as estado', 'tareas.observaciones')
            ->get();
    //$ordenes = Entrada::all();
    //$this->view->getEnvironment()->addGlobal('ordenesRegistro', $ordenes);
    return $this->view->render($response, 'registrations.twig',
      [
        'ordenesRegistro'=>$ordenes
      ]
    );
  }

  public function add(Request $request, Response $response)
  {
    $this->logger->debug('Add Registration Page - Dispatched ');
    $clientes = Cliente::all();
    return $this->view->render(
      $response,
      'registration.twig',
      [
        'clientes'=>$clientes
      ]);
  }

  public function postAdd(Request $request, Response $response)
  {
    $this->logger->debug('Add Registration Order - Processed ');
    $proceso = $this->workflow->start('Entradas', $request->getParam('observaciones'));
    $files = $request->getUploadedFiles();
    if (!empty($files['archivo'])) {
      $newfile = $files['archivo'];
      $proceso = $this->workflow->attach($proceso, $newfile, $request->getParam('descripcionArchivo'));
    }
    $order = new Entrada();
    $order->cliente_id = $request->getParam('cliente');
    $order->transportista = $request->getParam('transportista');
    $order->id_contenedor = $request->getParam('idContenedor');
    $order->proveedor = $request->getParam('proveedor');
    $order->origen = $request->getParam('origen');
    $order->BOL = $request->getParam('bol');
    $order->id_pallet = $request->getParam('pallets');
    $order->tipo = $request->getParam('tipo');
    $order->eta = \Datetime::createFromFormat('d/m/Y', $request->getParam('eta'));
    $order->etd = \Datetime::createFromFormat('d/m/Y', $request->getParam('etd'));
    $order->numero_factura = $request->getParam('factura');
    $order->precio_total = $request->getParam('precioTotal');
    $order->proceso_id = $proceso->id;

    $order->save();

    $this->flash->addMessage('success', 'Hemos guardado la información de la orden de entrada.');
    return $response->withRedirect($this->router->pathFor('registration.edit', ['id'=>$order->id] ));
  }

  public function edit(Request $request, Response $response, $args)
  {
    $this->logger->debug('Edit Registration Order - Dispatched');
    $clientes     = Cliente::all();
    $orden        = Entrada::with('proceso',
                        'proceso.archivos',
                        'partidas',
                        'proceso.tareas',
                        'proceso.tareas.estado',
                        'proceso.tareaActual',
                        'proceso.tareaActual.estado',
                        'proceso.tareaActual.estado.transiciones')->findOrFail($args['id']);
    $partidas     = $orden->partidas;
    $proceso      = $orden->proceso;
    $tareas       = $orden->proceso->tareas;
    $archivos     = $orden->proceso->archivos;
    $tarea        = $orden->proceso->tareaActual;
    $estado       = $orden->proceso->tareaActual->estado;
    $operaciones  = $orden->proceso->tareaActual->estado->transiciones;
    return $this->view->render($response, 'registration.twig',
      [
        'clientes'      => $clientes,
        'orden'         => $orden,
        'estado'        => $estado,
        'tarea'         => $tarea,
        'operaciones'   => $operaciones,
        'archivos'      => $archivos,
        'partidas'      => $partidas,
        'tareas'        => $tareas
      ]);
    }

    public function postEditEnProgreso(Request $request, Response $response, $args) {
      $this->logger->debug('Edit In Progress Registration Order - Processed ');
      $this->logger->debug($request->getParam('id'));

      // Verificamos si podemos avanzar la tarea a al siguiente estado.
      $validation = $this->validator->validate($request, [
        'id' => v::notEmpty()->UmtValidation()->FraccionValidation()->setName('orden'),
        'referenciaAA' => v::notEmpty()->alnum()->setName('Referencia aduanal'),
        'pedimento' => v::notEmpty()->alnum()->setName('Numero de pedimento')
      ]);
      if (!$validation->failed()) {
        $order                    = Entrada::with('proceso',
                                    'proceso.archivos',
                                    'partidas',
                                    'proceso.tareaActual',
                                    'proceso.tareaActual.estado',
                                    'proceso.tareaActual.estado.transiciones',
                                    'proceso.tareaActual.estado.transiciones.token')
                                  ->findOrFail($args['id']);
        $partidas                         = $order->partidas;
        $order->referencia_agente_aduanal = $request->getParam('referenciaAA');
        $order->numero_pedimento          = $request->getParam('pedimento');
        $order->save();

        $proceso = $order->proceso;
        $files = $request->getUploadedFiles();
        if (!empty($files['archivo'])) {
          $newfile = $files['archivo'];
          $proceso = $this->workflow->attach($proceso, $newfile, $request->getParam('descripcionArchivo'));
          $this->flash->addMessage('info', 'Adjuntamos el archivo a la orden de entrada.');
        }
        // hacemos la transicion en base a la operación.
        $proceso = $this->workflow->evaluation($proceso, $request->getParam('operacion'), $request->getParam('observaciones'));

        // Si es un estado terminal entonces agregamos el producto al inventario.
        if ($this->workflow->isFinished($proceso)) {
          foreach($partidas as $partida) {
            $producto = new Producto();
            $producto->lote                = $partida->lote;
            $producto->po                  = $partida->po;
            $producto->np                  = $partida->np;
            $producto->cantidad            = $partida->cantidad;
            $producto->cantidad_actual     = $partida->cantidad;
            $producto->consecutivo         = 0;
            $producto->numero_pedimento    = $order->numero_pedimento;
            $producto->descripcion         = $partida->descripcion;
            $producto->precio_unitario     = $partida->precio_unitario;
            $producto->peso_neto           = $partida->peso_neto;
            $producto->peso_bruto          = $partida->peso_bruto;
            $producto->id_pallet           = $partida->id_pallet;
            $producto->id_caja             = $partida->id_caja;
            $producto->fraccion_arancelaria= $partida->fraccion_arancelaria;
            $producto->umt                 = $partida->umt;
            $producto->save();

            $partida->producto_id         = $producto->id;
            $partida->save();
          }
        }
        $this->flash->addMessage('success', 'Hemos guardado la información de la orden de entrada.');
      } else {
        $this->flash->addMessage('error', "Los datos que nos proporcionaste no son correctos.");
      }
      return $response->withRedirect($this->router->pathFor('registration.edit', ['id'=>$args['id']] ));
    }

    public function postEdit(Request $request, Response $response, $args)
    {
      $this->logger->debug('Edit Registration Order - Processed ');
      // Verificamos si podemos avanzar la tarea a al siguiente estado.
      $validation = $this->validator->validate($request, [
        'id' => v::notEmpty()->EntradaPartidasValidation()->setName('orden')
      ]);
      if (!$validation->failed()) {
        $order                    = Entrada::with('proceso',
                                    'proceso.archivos',
                                    'partidas',
                                    'proceso.tareaActual',
                                    'proceso.tareaActual.estado',
                                    'proceso.tareaActual.estado.transiciones',
                                    'proceso.tareaActual.estado.transiciones.token')->findOrFail($args['id']);
        $order->cliente_id        = $request->getParam('cliente');
        $order->transportista     = $request->getParam('transportista');
        $order->id_contenedor     = $request->getParam('idContenedor');
        $order->proveedor         = $request->getParam('proveedor');
        $order->origen            = $request->getParam('origen');
        $order->BOL               = $request->getParam('bol');
        $order->id_pallet         = $request->getParam('pallets');
        $order->tipo              = $request->getParam('tipo');
        $order->eta               = \Datetime::createFromFormat('d/m/Y', $request->getParam('eta'));
        $order->etd               = \Datetime::createFromFormat('d/m/Y', $request->getParam('etd'));
        $order->numero_factura    = $request->getParam('factura');
        $order->precio_total      = $request->getParam('precioTotal');

        $order->save();

        $proceso = $order->proceso;
        $files = $request->getUploadedFiles();
        if (!empty($files['archivo'])) {
          $newfile = $files['archivo'];
          $proceso = $this->workflow->attach($proceso, $newfile, $request->getParam('descripcionArchivo'));
          $this->flash->addMessage('info', 'Adjuntamos el archivo la orden de entrada.');
        }
        // hacemos la transicion en base a la operación.
        $proceso = $this->workflow->evaluation($proceso, $request->getParam('operacion'), $request->getParam('observaciones'));

        $this->flash->addMessage('success', 'Hemos guardado la información de la orden de entrada.');
      } else {
        $this->flash->addMessage('error', 'Los datos capturados no son correctos. Favor de corregirlos.');
      }
      return $response->withRedirect($this->router->pathFor('registration.edit', ['id'=>$args['id']] ));
    }

    /**
      Devuelve un archivo leido.
    */
    public function showFile(Request $request, Response $response, $args)
    {
      $this->logger->debug('Show File - Processed ');
      $archivo = $this->workflow->getArchivoById($args['id']);
      $this->logger->debug($archivo);
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
      return $response->withRedirect($this->router->pathFor('registration.edit', ['id'=>$args['orden_id']] ));
    }

/**
  Borra una orden de entrada de la base de datos.
*/
    public function delete(Request $request, Response $response, $args)
    {
      $this->logger->debug('Delete Order - Processed');
      $order = Entrada::with('partidas')
        ->findOrFail($args['id']);
      $order->partidas()->delete();
      $order->delete();
      $this->flash->addMessage('success', 'Borramos la orden de entrada.');
      return $response->withRedirect($this->router->pathFor('registrations' ));
    }

    public function addItem(Request $request, Response $response, $args)
    {
      $this->logger->debug('Show Item Registration Order - Dispatched');
      $order          = Entrada::with('partidas')
        ->findOrFail($args['orden_id']);

      return $this->view->render($response, 'itemRegistration.twig',
        ['orden'=>$order]
      );
    }

    public function postAddItem(Request $request, Response $response, $args)
    {
      $this->logger->debug('Show Item Registration Order - Processed');
      $url = $this->router->pathFor('registration.item.add', ['orden_id'=>$args['orden_id']] );
      $order    = Entrada::with('partidas')->findOrFail($args['orden_id']);
      $partida  = new EntradaProducto();
      $partida->np                    = $request->getParam('numeroParte');
      $partida->descripcion           = $request->getParam('descripcion');
      $partida->cantidad              = $request->getParam('cantidad');
      $partida->po                    = $request->getParam('po');
      $partida->lote                  = $request->getParam('lote');
      $partida->precio_unitario       = $request->getParam('precioUnitario');
      $partida->precio_total          = $request->getParam('precioTotal');
      $partida->peso_neto             = $request->getParam('pesoNeto');
      $partida->peso_bruto            = $request->getParam('pesoBruto');
      $partida->id_pallet             = $request->getParam('idPallets');
      $partida->id_caja               = $request->getParam('idCajas');
      $partida->fraccion_arancelaria  = $request->getParam('fraccionArancelaria');
      $partida->umt                   = $request->getParam('umt');
      $order->partidas()->save($partida);

      $order->precio_total  = $order
        ->partidas()
        ->sum('precio_total');
      $order->save();

      if ($request->getParam('operacion') == "Guardar") {
        $url = $this->router->pathFor('registration.edit', ['id'=>$args['orden_id']] );
      }
      $this->flash->addMessage('success', 'Nueva partida agregada a la orden de ingreso.');
      return $response->withRedirect($url);
    }

    public function editItem(Request $request, Response $response, $args) {
      $this->logger->debug('Edit Item Registration Order - Dispatched');
      $partida          = EntradaProducto::with('entrada')
        ->findOrFail($args['partida_id']);

      return $this->view->render($response, 'itemRegistration.twig',
        [
          'orden'   =>$partida->entrada,
          'partida' =>$partida
        ]);

    }

    public function postEditItem(Request $request, Response $response, $args)
    {
      $this->logger->debug('Show Item Registration Order - Processed');
      $this->logger->debug($request->getParam('pesoNeto') . ' ' . $request->getParam('pesoBruto'));
      $url = $this->router->pathFor('registration.item.add', ['orden_id'=>$args['orden_id']] );

      $partida  = EntradaProducto::with('entrada')->findOrFail($args['partida_id']);
      $partida->np                    = $request->getParam('numeroParte');
      $partida->descripcion           = $request->getParam('descripcion');
      $partida->cantidad              = $request->getParam('cantidad');
      $partida->po                    = $request->getParam('po');
      $partida->lote                  = $request->getParam('lote');
      $partida->precio_unitario       = $request->getParam('precioUnitario');
      $partida->precio_total          = $request->getParam('precioTotal');
      $partida->peso_neto             = $request->getParam('pesoNeto');
      $partida->peso_bruto            = $request->getParam('pesoBruto');
      $partida->id_pallet             = $request->getParam('idPallets');
      $partida->id_caja               = $request->getParam('idCajas');
      $partida->fraccion_arancelaria  = $request->getParam('fraccionArancelaria');
      $partida->umt                   = $request->getParam('umt');
      $partida->save();

      $entrada = Entrada::find($args['orden_id']);
      $entrada->precio_total  = $entrada->partidas()
        ->sum('precio_total');
      $entrada->save();

      if ($request->getParam('operacion') == "Guardar") {
        $url = $this->router->pathFor('registration.item.edit', ['orden_id'=>$args['orden_id'], 'partida_id'=>$args['partida_id']] );
      }
      $this->flash->addMessage('success', 'Actualizamos la información de la partida de la orden de ingreso.');
      return $response->withRedirect($url);
    }


    public function deleteItem(Request $request, Response $response, $args)
    {
      $this->logger->debug('Delete Item Registration Order - Processed');
      $partida = EntradaProducto::with('entrada')->find($args['partida_id']);
      $orden = $partida->entrada;
      $partida->delete();
      $url = $this->router->pathFor('registration.edit', ['id'=>$orden->id] );
      $this->flash->addMessage('success', 'Quitamos la partida de la orden de ingreso.');
      return $response->withRedirect($url);
    }

/**
  Genera los recibos.
*/
    public function receipt(Request $request, Response $response, $args) {
      $this->logger->debug('Reciepts Registration Order - Processed');
      $objReader = \PHPExcel_IOFactory::createReader('Excel5');
      $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/recibo.xls");
      $orden                    = Entrada::with('partidas')->findOrFail($args['id']);

      //  Get the current sheet with all its newly-set style properties
      $objWorkSheetBase = $objPHPExcel->getSheet();

      foreach ($orden->partidas as $partida) {
        //  Create a clone of the current sheet, with all its style properties
        $objWorkSheet1 = clone $objWorkSheetBase;
        //  Set the newly-cloned sheet title
        $objWorkSheet1->setTitle('NP-' . $partida->np);

        $objWorkSheet1
          ->setCellValue('C12', $orden->id_contenedor)
          ->setCellValue('H10', date('d/m/Y'))
          ->setCellValue('G14', $partida->po . ' / ' . $partida->lote)
          ->setCellValue('G16', $partida->cantidad)
          ->setCellValue('C18', $partida->np)
          ->setCellValue('G18', $partida->id_caja)
          ->setCellValue('C20', $partida->id_pallet);

        //  Attach the newly-cloned sheet to the $objPHPExcel workbook
        $objPHPExcel->addSheet($objWorkSheet1);
      }

      $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
      $file = 'php://output';
      $objWriter->save($file);

      return $response->withHeader('Content-Type', 'application/vnd.ms-excel')
                    ->withHeader('Content-Disposition', 'attachment;filename=recibos.xls')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Type', 'application/download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'public');
    }

    public function packing(Request $request, Response $response, $args)
    {
      $objReader = \PHPExcel_IOFactory::createReader('Excel5');
      $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/packing.xls");

      $order                    = Entrada::with('proceso',
                                  'proceso.archivos',
                                  'partidas',
                                  'proceso.tareaActual',
                                  'proceso.tareaActual.estado',
                                  'proceso.tareaActual.estado.transiciones',
                                  'proceso.tareaActual.estado.transiciones.token')->findOrFail($args['id']);
      $partidas = $order->partidas;

      $objPHPExcel->getActiveSheet()->setCellValue('N11', date('d/m/Y')); // Fecha del Dia
      $objPHPExcel->getActiveSheet()->setCellValue('L11', $order->numero_factura); //Numero de orden
      $objPHPExcel->getActiveSheet()->setCellValue('D23', date('d/m/Y')); //Fecha estimada de arribo
      $objPHPExcel->getActiveSheet()->setCellValue('M23', $order->etd); //Fecha de embarque.
      $id_pallet = "";
      $rowBase = $row = 28;
      foreach ($partidas as $partida) {
        if (empty($id_pallet)) {
          $id_pallet = $partida->id_pallet;
        } elseif ($id_pallet != $partida->id_pallet) {
          $id_pallet = "VARIOS";
        }

        $objPHPExcel->setActiveSheetIndex(0)
          ->setCellValue('A' . $row, $partida->id_pallet)
          ->setCellValue('C' . $row, $partida->id_caja)
          ->setCellValue('E' . $row, $partida->descripcion)
          ->setCellValue('E' . ($row + 1), $partida->np)
          ->setCellValue('I' . $row, $partida->cantidad)
          ->setCellValue('K' . $row, $partida->peso_neto)
          ->setCellValue('M' . $row, $partida->peso_bruto);
          $row += 2;
      }
      $objPHPExcel->getActiveSheet()->setCellValue('A23', $id_pallet); //Order Pallets

      $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
      $file = 'php://output';
      $objWriter->save($file);

      return $response->withHeader('Content-Type', 'application/vnd.ms-excel')
                    ->withHeader('Content-Disposition', 'attachment;filename=packing.xls')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Type', 'application/download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'public');
    }

    public function invoice(Request $request, Response $response, $args)
    {
      $objReader = \PHPExcel_IOFactory::createReader('Excel5');
      $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/invoice.xls");

      $order                    = Entrada::with('proceso',
                                  'proceso.archivos',
                                  'partidas',
                                  'proceso.tareaActual',
                                  'proceso.tareaActual.estado',
                                  'proceso.tareaActual.estado.transiciones',
                                  'proceso.tareaActual.estado.transiciones.token')->findOrFail($args['id']);
      $partidas = $order->partidas;

      $objPHPExcel->getActiveSheet()->setCellValue('N13', date('d/m/Y'));
      $objPHPExcel->getActiveSheet()->setCellValue('L13', $order->numero_factura); //Order
      $objPHPExcel->getActiveSheet()->setCellValue('D25', date('d/m/Y')); //Fecha de arribo
      $objPHPExcel->getActiveSheet()->setCellValue('M25', $order->etd); //Fecha de embarque
      $id_pallet = "";
      $rowBase = $row = 30;
      foreach ($partidas as $partida) {
        if (empty($id_pallet)) {
          $id_pallet = $partida->id_pallet;
        } elseif ($id_pallet != $partida->id_pallet) {
          $id_pallet = "VARIOS";
        }

        $objPHPExcel->setActiveSheetIndex(0)
          ->setCellValue('A' . $row, $partida->cantidad)
          ->setCellValue('D' . $row, $partida->np)
          ->setCellValue('G' . $row, $partida->descripcion)
          ->setCellValue('G' . ($row + 1), "PO " . $partida->po . " / " . $partida->lote)
          ->setCellValue('K' . $row, $partida->precio_unitario);
          $row += 2;
      }
      $objPHPExcel->getActiveSheet()->setCellValue('A25', $id_pallet); //Order Pallets

      $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
      $file = 'php://output';
      $objWriter->save($file);

      return $response->withHeader('Content-Type: application/vnd.ms-excel')
                    ->withHeader('Content-Disposition: attachment;filename=invoice.xls')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Type', 'application/download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'public');
    }
  }
