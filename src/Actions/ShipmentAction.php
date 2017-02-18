<?php

namespace App\Actions;

use Slim\Http\Request;
use Slim\Http\Response;

use \App\Models\Entrada;
use \App\Models\Producto;

/**
 * Manejo de cuentas de usuarios de usuarios.
 */
class ShipmentAction extends Action
{
  public function searchByNP(Request $request, Response $response, $args) {

    $this->logger->debug('Search Product By NumberPart WS - Dispatched ');
    $productos = Producto::
      where('numero_pedimento', '=', $args['numero_pedimento'])
      ->where('np', 'like', $request->getParam('TERM') . '%')
      ->take(10)
      ->get();
    $response = $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($productos));
    return $response;

  }

  public function search(Request $request, Response $response, $args) {

    $this->logger->debug('Search Product WS - Dispatched ');
    $productos = Producto::findOrFail($args['id']);

    $response = $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($productos));
    return $response;
  }

    public function listing(Request $request, Response $response)
    {
      $this->logger->debug('Products Page - Dispatched ');
      $productos = Producto::all();
      return $this->view->render($response, 'shipments.twig' ,
      ['productos'=>$productos]);
    }

    public function export(Request $request, Response $response)
    {
        $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objPHPExcel = $objReader->load(__DIR__ . "/../../resources/excel/inventario.xls");

        $productos = Producto::all();

            $baseRow = 3;

            foreach($data as $r => $dataRow) {
                $row = $baseRow + $r;

                $objPHPExcel->getActiveSheet()->insertNewRowBefore($row,1);

                $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, $r+1)
                                              ->setCellValue('B'.$row, $dataRow['N/P'])
                                              ->setCellValue('C'.$row, $dataRow['Lote'])
                                              ->setCellValue('D'.$row, $dataRow['PO'])
                                              ->setCellValue('E'.$row, $dataRow['PN'])
                                              ->setCellValue('R'.$row, $dataRow['Descripcion'])
                                              ->setCellValue('J'.$row, $dataRow['Precio unitario'])
                                              ->setCellValue('H'.$row, $dataRow['Peso neto'])
                                              ->setCellValue('I'.$row, $dataRow['Peso bruto'])
                                              ->setCellValue('G'.$row, $dataRow['id Pallet'])
                                              ->setCellValue('K'.$row, $dataRow['id Caja'])
                                              ->setCellValue('L'.$row, $dataRow['Fraccion arancelaria'])
                                              ->setCellValue('Y'.$row, $dataRow['UMT'])
                                              ->setCellValue('N'.$row, $dataRow['Fecha de creación'])
                                              ->setCellValue('O'.$row, $dataRow['Fecha actualización']);
        }

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $file = 'php://output';
        $objWriter->save($file);

        return $response->withHeader('Content-Type', 'application/vnd.ms-excel')
                    ->withHeader('Content-Disposition', 'attachment;filename=Inventario.xls')
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Type', 'application/download')
                    ->withHeader('Content-Description', 'File Transfer')
                    ->withHeader('Content-Transfer-Encoding', 'binary')
                    ->withHeader('Expires', '0')
                    ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'public');
    }
}
