<?php

/**
 * Para utilizar este pequeño script desde consola ejecutamos
 * php migration.php drop     //Para eliminar tablas
 * php migration.php create   //Para crear las tablas
 *
 * Configurar los datos de conexión a la base de datos.
 */

require __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../app/config_override.php')) {
    require __DIR__ . '/../app/config_override.php';
}

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use App\Models\Cliente;
use App\Models\Destinatario;

use App\Workflow\Models\Automata;
use App\Workflow\Models\Estado;
use App\Workflow\Models\Transicion;
use App\Workflow\Models\Alfabeto;

/**
 * Carga la Configuración y devuelve un objeto de tipo cápsula de eloquent
 *
 * @return \Illuminate\Database\Schema\Builder
 */
function setup()
{
    global $config;

    $capsule = new Capsule();
    $capsule->addConnection($config['settings']['db']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule->schema();
}

/**
 * Elimina las tablas de la base de datos.
 */
function drop()
{
    $schema = setup();
    try {
        $schema->dropIfExists('notificaciones');
        $schema->dropIfExists('tareas');

        $schema->dropIfExists('activations');
        $schema->dropIfExists('persistences');
        $schema->dropIfExists('reminders');
        $schema->dropIfExists('roles');
        $schema->dropIfExists('role_users');
        $schema->dropIfExists('throttle');
        $schema->dropIfExists('users');
        $schema->dropIfExists('salida_producto');
        $schema->dropIfExists('entrada_producto');
        $schema->dropIfExists('salidas');
        $schema->dropIfExists('entradas');
        $schema->dropIfExists('productos');
        $schema->dropIfExists('clientes');
        $schema->dropIfExists('destinatarios');
        $schema->dropIfExists('archivos');
        $schema->dropIfExists('procesos');
        $schema->dropIfExists('transiciones');
        $schema->dropIfExists('estados');
        $schema->dropIfExists('alfabetos');
        $schema->dropIfExists('automatas');

    } catch (\Exception $e) {
        echo "Unable to drop my_table: {$e->getMessage()}";
        echo "";
    }
}

/**
 * Crea tablas en la base de datos
 */
function create()
{
    $schema = setup();
    try {
        $schema->create('activations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
        });

        $schema->create('persistences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('code');
        });

        $schema->create('reminders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('code');
            $table->boolean('completed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('code');

        });

        $schema->create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->string('name');
            $table->text('permissions')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('slug');
        });

        $schema->create('role_users', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->nullableTimestamps();

            $table->engine = 'InnoDB';
            $table->primary(['user_id', 'role_id']);
        });

        $schema->create('throttle', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('type');
            $table->string('ip')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->index('user_id');
        });

        $schema->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->string('password');
            $table->text('permissions')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->unique('email');
        });

        $schema->create('notificaciones', function (Blueprint $table) {
          $table->increments('id');
          $table->string('tipo');
          $table->string('mensaje');
          $table->string('accion');
          $table->date('vencimiento');
          $table->unsignedInteger('user_id');

          $table->timestamps();

          $table->engine = 'InnoDB';

          $table->foreign('user_id')
            ->references('id')->on('users');
        });

        $schema->create('clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->string('domicilio');
            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->unique('nombre');
        });

        $schema->create('destinatarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre');
            $table->string('domicilio');
            $table->string('localidad');
            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->unique('nombre');
        });

        $schema->create('productos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('lote');
            $table->string('po');
            $table->string('np');
            $table->string('descripcion');
            $table->decimal('cantidad', 13, 0);           // Cantidad de piezas recibidas en el embarque.
            $table->decimal('cantidad_actual', 13, 0);    // Cantidad de piezas que tenemos actualmente en el inventario.
            $table->decimal('consecutivo', 13, 0);        // Consecutivo de veces que se han extraido piezas de ese lote, este producto.
            $table->string('numero_pedimento');
            $table->decimal('precio_unitario', 20, 6);
            $table->decimal('peso_neto', 13, 3);
            $table->decimal('peso_bruto', 13, 3);
            $table->string('id_pallet');
            $table->string('id_caja');
            $table->decimal('fraccion_arancelaria', 20, 6);
            $table->string('umt');

            $table->timestamps();

            $table->engine = 'InnoDB';
        });

        $schema->create('automatas', function (Blueprint $table) {
          $table->increments('id');
          $table->string('nombre');
          $table->string('descripcion');
          $table->unsignedInteger('estadoInicial_id')->nullable();
          $table->date('kpi');

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

        });

        $schema->create('estados', function (Blueprint $table) {
          $table->increments('id');
          $table->string('nombre');
          $table->string('descripcion');
          $table->integer('toleracia');               // Indica el numero de dias que tiene de tolerancia para la atención de este estado antes de lanzar una alerta.
          $table->unsignedInteger('automata_id');

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('automata_id')
            ->references('id')->on('automatas');

        });

        $schema->create('alfabetos', function (Blueprint $table) {
          $table->increments('id');
          $table->string('nombre');
          $table->string('descripcion');
          $table->unsignedInteger('automata_id');

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('automata_id')
            ->references('id')->on('automatas');

        });

        $schema->create('transiciones', function (Blueprint $table) {
          $table->increments('id');
          $table->string('nombre');
          $table->string('descripcion');
          $table->unsignedInteger('origen_id');           // Estado origen.
          $table->unsignedInteger('destino_id');          // Estado final.
          $table->unsignedInteger('token_id');            // Alfabeto.
          $table->unsignedInteger('automata_id');         // Automata al cual pertenecen las transiciones

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas  con automata
          $table->foreign('automata_id')
            ->references('id')->on('automatas');

          // Referencias foraneas con estado origen
          $table->foreign('origen_id')
            ->references('id')->on('estados');

          // Referencias foraneas
          $table->foreign('destino_id')
            ->references('id')->on('estados');

          // Referencias foraneas con el alfabeto
          $table->foreign('token_id')
            ->references('id')->on('alfabetos');

        });

        $schema->create('procesos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('automata_id');
            $table->string('nombre');
            $table->string('descripcion');
            $table->date('fecha_alta');
            $table->date('fecha_vencimiento');
            $table->unsignedInteger('tareaActual_id')->nullable();

            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->foreign('automata_id')
              ->references('id')->on('automatas');

        });

        $schema->create('archivos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('archivo');
            $table->string('descripcion');
            $table->date('fecha_alta');
            $table->unsignedInteger('proceso_id');

            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->foreign('proceso_id')
              ->references('id')->on('procesos');
        });

        $schema->create('tareas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('observaciones');
            $table->date('fecha_cierre');
            $table->date('fecha_planeada');
            $table->unsignedInteger('proceso_id');
            $table->unsignedInteger('estado_id');

            $table->timestamps();

            $table->engine = 'InnoDB';

            $table->foreign('proceso_id')
              ->references('id')->on('procesos');
            $table->foreign('estado_id')
              ->references('id')->on('estados');
            $table->foreign('user_id')
              ->references('id')->on('users');
        });

        $schema->create('entradas', function (Blueprint $table) {
          // Campos
          $table->increments('id');
          $table->string('transportista');
          $table->string('BOL');
          $table->string('id_contenedor');
          $table->string('proveedor');
          $table->string('origen');
          $table->string('id_pallet');
          $table->string('tipo');
          $table->date('eta');
          $table->date('etd');
          $table->string('numero_factura');
          $table->string('numero_pedimento');
          $table->string('referencia_agente_aduanal');
          $table->decimal('precio_total', 13, 6);
          $table->unsignedInteger('cliente_id');
          $table->unsignedInteger('proceso_id');

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('cliente_id')
            ->references('id')->on('clientes');
          $table->foreign('proceso_id')
            ->references('id')->on('procesos');
        });

        $schema->create('entrada_producto', function (Blueprint $table) {
          // Campos
          $table->increments('id');
          $table->string('np');
          $table->string('descripcion');
          $table->string('cantidad');
          $table->string('po');
          $table->string('lote');
          $table->decimal('precio_unitario', 20, 6);
          $table->decimal('precio_total', 20, 6);
          $table->decimal('peso_neto', 13, 3);
          $table->decimal('peso_bruto', 13, 3);
          $table->string('id_pallet');
          $table->string('id_caja');
          $table->decimal('fraccion_arancelaria', 20, 6);
          $table->string('umt');
          $table->unsignedInteger('entrada_id');
          $table->unsignedInteger('producto_id')->nullable();

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('entrada_id')
            ->references('id')->on('entradas');
        });

        $schema->create('salidas', function (Blueprint $table) {
          $table->increments('id');
          $table->date('fecha_extraccion');
          $table->string('numero_pedimento');
          $table->unsignedInteger('destinatario_id');
          $table->unsignedInteger('proceso_id');
          $table->unsignedInteger('consecutivo');
          
          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('proceso_id')
            ->references('id')->on('procesos');
          // Referencias foraneas
          $table->foreign('destinatario_id')
            ->references('id')->on('destinatarios');

        });

        $schema->create('salida_producto', function (Blueprint $table) {
          // Campos
          $table->increments('id');
          $table->string('np');
          $table->string('wsr');
          $table->string('descripcion');
          $table->string('cantidad');
          $table->string('po');
          $table->string('lote');
          $table->string('id_caja')->nullable();
          $table->string('id_pallet')->nullable();
          $table->decimal('peso_neto', 13, 3);
          $table->decimal('peso_bruto', 13, 3);
          $table->decimal('precio_unitario', 20, 6);
          $table->decimal('precio_total', 20, 6);
          $table->unsignedInteger('salida_id');
          $table->unsignedInteger('producto_id')->nullable();

          // Campos ocultos
          $table->timestamps();

          // Motor de BD donde se va implementar
          $table->engine = 'InnoDB';

          // Referencias foraneas
          $table->foreign('salida_id')
            ->references('id')->on('salidas');
        });
    } catch (\Exception $e) {
        echo "Unable to create my_table: {$e->getMessage()}";
        echo "";
    }
}

/*
  Agrega la información a las tablas.
*/
function inicialize($nombre="Administrador", $apellido="VisionWap", $email="visionwap@gmail.com", $password="AAAaaa123+") {
  $schema = setup();
  $administratorRol = Sentinel::getRoleRepository()->createModel()->create([
     'name' => 'Administrador',
     'slug' => 'Administrador del sistema'
   ]);
  $administratorRol->permissions = [
    'account.list'              => true,      // Permiso para listar los usuarios.
    'account.add'               => true,      // Permiso para agregar un usuario.
    'account'                   => true,      // Permiso para editar un usuario.
    'account.delete'            => true,      // Permiso para borrar un usuario.

    'registration.list'         => true,      // Permiso para listar las ordenes de registro
    'registration.receipt'      => true,      // Permiso para generar el packaging
    'registration.packing'      => true,      // Permiso para generar el packaging
    'registration.invoice'      => true,      // Permiso para generar el invoice
    'registration.add'          => true,      // Permiso para agregar una orden de registro
    'registration.drop.file'    => true,      // Permiso para agregar una borrar un archivo adjunto a una solicitud de entrada
    'registration.show.file'    => true,      // Permiso para agregar ver un archivo adjunto a una solicitud de entrada.
    'registration.delete'       => true,      // Permiso para borrar una orden de registro
    'registration'              => true,      // Permiso para editar una orden de registro
    'registration.inprogress'   => true,      // Permiso para editar una orden de registro
    'registration.item'         => true,      // Permiso para editar una partida de la orden de registro
    'registration.item.add'     => true,      // Permiso para agregar un partida de la orden de registro
    'registration.item.delete'  => true,      // Permiso para borrar una partida de la orden de registro

    'extraction.list'           => true,      // Permiso para listar las ordenes de extraccion
    'extraction.export'         => true,      // Permiso para exportar las ordenes de extraccion
    'extraction.add'            => true,      // Permiso para agregar una orden de extraccion
    'extraction.delete'         => true,      // Permiso para borrar una orden de registro
    'extraction.argo'           => true,      // Permiso para generar el documento argo
    'extraction.bol'            => true,      // Permiso para generar el documento bol
    'extraction'                => true,      // Permiso para extraer una orden de
    'extraction.item.add'       => true,      // Permiso para agregar una partida a la orden de extraccion
    'extraction.item'           => true,      // Permiso para editar una partida de la orden de extraccion
    'extraction.item.delete'    => true,      // Permiso para borrar una partida de la orden de extraccion
    'extraction.add.product'    => true,      // Permiso para agregar un producto a la orden de extraccion vigente

    'shipment.searchByNP'       => true,      // Permiso para listar los productos del inventario.
    'shipment.search'           => true,      // Permiso para listar los productos del inventario.
    'shipment.list'             => true,      // Permiso para listar los productos del inventario.
    'shipment.export'           => true,      // Permiso para exportar la lista de inventarios.

    'configuration'             => true       // Permiso para editar la configuración de la aplicación.
  ];
  $administratorRol->save();
  echo "Se agrega el rol del administrador del sistema y sus permisos.<br/>";

  $operadorRol = Sentinel::getRoleRepository()->createModel()->create([
     'name' => 'Operador',
     'slug' => 'Operadores del sistema'
   ]);

  $operadorRol->permissions = [
    'account.list'            => false,     // Permiso para listar los usuarios.
    'account.add'             => false,     // Permiso para agregar un usuario.
    'account'                 => false,     // Permiso para editar un usuario.
    'account.delete'          => true,      // Permiso para borrar un usuario.

    'registration.list'       => true,      // Permiso para listar las ordenes de registro
    'registration.receipt'    => true,      // Permiso para generar el packaging
    'registration.packing'    => true,      // Permiso para generar el packaging
    'registration.invoice'    => true,      // Permiso para generar el invoice
    'registration.add'        => true,      // Permiso para agregar una orden de registro
    'registration.drop.file'  => true,      // Permiso para agregar una borrar un archivo adjunto a una solicitud de entrada
    'registration.show.file'  => true,      // Permiso para agregar ver un archivo adjunto a una solicitud de entrada.
    'registration.delete'     => true,      // Permiso para borrar una orden de registro
    'registration'            => true,      // Permiso para editar una orden de registro
    'registration.inprogress' => true,      // Permiso para editar una orden de registro
    'registration.item'       => true,      // Permiso para editar una partida de la orden de registro
    'registration.item.add'   => true,      // Permiso para agregar un partida de la orden de registro
    'registration.item.delete'=> true,      // Permiso para borrar una partida de la orden de registro

    'extraction.list'         => true,      // Permiso para listar las ordenes de extraccion
    'extraction.export'       => true,      // Permiso para exportar las ordenes de extraccion
    'extraction.add'          => true,      // Permiso para agregar una orden de extraccion
    'extraction.delete'       => true,      // Permiso para borrar una orden de registro
    'extraction.argo'         => true,      // Permiso para generar el documento argo
    'extraction.bol'          => true,      // Permiso para generar el documento bol
    'extraction'              => true,      // Permiso para extraer una orden de
    'extraction.item.add'     => true,      // Permiso para agregar una partida a la orden de extraccion
    'extraction.item'         => true,      // Permiso para editar una partida de la orden de extraccion
    'extraction.item.delete'  => true,      // Permiso para borrar una partida de la orden de extraccion
    'extraction.add.product'  => true,      // Permiso para agregar un producto a la orden de extraccion vigente

    'shipment.searchByNP'     => true,      // Permiso para listar los productos del inventario.
    'shipment.search'         => true,      // Permiso para listar los productos del inventario.
    'shipment.list'           => true,      // Permiso para listar los productos del inventario.
    'shipment.export'         => false,     // Permiso para exportar la lista de inventarios.

    'configuration'           => false      // Permiso para editar la configuración de la aplicación.
  ];
  $administratorRol->save();
  echo "Se agrega el rol del administrador del sistema y sus permisos.<br/>";

  $credentials =
  [
    'first_name' => $nombre,
    'last_name' => $apellido,
    'email' => $email,
    'password' => $password,
  ];

  $user = Sentinel::registerAndActivate($credentials);
  $role = Sentinel::findRoleByName('Administrador');
  $role->users()->attach($user);
  echo "El usuario administrador y sus permisos.<br/>";

  $cliente = Cliente::create();
  $cliente->nombre = 'Taurus International Corporation';
  $cliente->domicilio = '';
  $cliente->save();
  echo "Se agrega agrega el cliente inicial para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'TRANSMISIONES Y EQUIPOS MECANICOS S.A. DE C.V.';
  $cliente->domicilio = 'AV 5 DE FEBRERO #2115 FRACC. INDUSTRIAL BENITO JUAREZ';
  $cliente->localidad = 'QUERETARO, QRO. MEXICO CP 76120';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CNH INDUSTRIAL, S.A. DE C.V.';
  $cliente->domicilio = 'AVE. 5 DE FEBRERO # 2117 ZONA INDUSTRIAL BENITO JUAREZ';
  $cliente->localidad = 'QUERETARO, QRO. MEXICO CP 76130';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'STEERINGMEX S. DE R.L. DE C.V.';
  $cliente->domicilio = 'SANTA ROSA DE VITERBO #12 PARQUE IND FINSA';
  $cliente->localidad = 'EL MARQUES, QRO. MEXICO CP 76246';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'TAURUS INTERNATIONAL';
  $cliente->domicilio = '275 NORTH FRANKLIN TURNPIKE';
  $cliente->localidad = 'RAMSEY, NJ 07446';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'TRW SISTEMAS DE DIRECCIONES, S. DE R.L. DE C.V.';
  $cliente->domicilio = 'AV. DE LAS FUENTES #29 PARQUE IND. B. QUINTANA';
  $cliente->localidad = 'EL MARQUES, QRO. MEXICO CP 46246';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'DELPHI DE MEXICO S DE R.L DE C.V';
  $cliente->domicilio = 'AVE. HERMANOS ESCOBAR #5756 COL. FOVISSSTE CHAMIZAL';
  $cliente->localidad = 'CD. JUAREZ, CH. MEXICO CP 32310';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'RIDE CONTROL MEXICANA S. DE R.L. DE C.V.';
  $cliente->domicilio = 'AVENIDA EL TEPEYAC NO. 110 PARQUE IND. EL TEPEYAC';
  $cliente->localidad = 'EL MARQUES, QUERETARO CP 76020';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'FRENOS Y MECANISMOS S DE RL DE CV';
  $cliente->domicilio = 'LA GRIEGA # 101 , PARQUE INDUSTRIAL QUERETARO';
  $cliente->localidad = 'SANTA ROSA JAUREGUI, QUERETARO. CP 76220';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'SIEMENS S.A. DE C.V.';
  $cliente->domicilio = 'EJERCITO NACIONAL NO 350, PISO 3';
  $cliente->localidad = 'DELEGACION MIGUEL HIDALGO, MEXICO DF CP 11560';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CNH CURITIBA VIA ISC BURLINGTON';
  $cliente->domicilio = '2850 MT. PLEASANT STREET';
  $cliente->localidad = 'BURLINGTON, IA 52601';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CNH LATIN AMERICA LTDA';
  $cliente->domicilio = 'AV JUSCELINO K. DE OLIVEIRA 11825';
  $cliente->localidad = 'CURITIBA-PR, BRASIL 81.450-903';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'DELPHI CENTEC II';
  $cliente->domicilio = 'BLVD. ISIDRO LOPEZ ZERTUCHE #4890';
  $cliente->localidad = 'SALTILLO COAHUILA, MEXICO CP 25230';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CNH COMPONENTES S.A. DE C.V.';
  $cliente->domicilio = 'AVE. 5 DE FEBRERO #2117 FRACC. IND BENITO JUAREZ';
  $cliente->localidad = 'QUERETARO, QRO. MEXICO CP 76130';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CNH COMERCIAL SA DE CV';
  $cliente->domicilio = 'AVE. 5 DE FEBRERO #2117 FRACC. IND BENITO JUAREZ';
  $cliente->localidad = 'QUERETARO, QRO. MEXICO CP 76130';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'GRUPO PALANCAS S.A. DE C.V.';
  $cliente->domicilio = 'AV. MANANTIALES NO. 8 PARQUE IND. BERNARDO QUINTANA';
  $cliente->localidad = 'EL MARQUES, QRO. CP 76246';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'PRODUCTOS ELECTROMECANICOS BAC S. DE R.L. DE C.V.';
  $cliente->domicilio = 'PONIENTE 4 SN';
  $cliente->localidad = 'CIUDAD INDUSTRIAL, TAMAULIPAS CP 87499';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'CENTRO TECNICO HERRAMENTAL S DE RL DE CV';
  $cliente->domicilio = 'AVE. HERMANOS ESCOBAR #5756 COL. FOVISSSTE CHAMIZAL';
  $cliente->localidad = 'CD. JUAREZ, CH. MEXICO CP 32310';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  $cliente = Destinatario::create();
  $cliente->nombre = 'DESTRUCCION';
  $cliente->domicilio = 'CONOCIDO';
  $cliente->localidad = 'CONOCIDO';
  $cliente->save();
  echo "Se agrega agrega el destintario para skyline.<br/>";

  // Ahora inicializamos el automata de Entradas que vamos a utilizar.
  $entradaDef = Automata::create();
  $entradaDef->nombre = 'Entradas';
  $entradaDef->descripcion = 'Ordenes de entrada para el ingreso de mercancias de un contenedor';
  $entradaDef->save();

  // Creamos los estados que vamos a utilizar en el proceso de entrada.
  $enCaptura =  new Estado();
  $enCaptura->nombre = 'En captura';
  $enCaptura->descripcion = 'Estado inicial del flujo de Entradas. Solo se esta capturando la información de un embarque.';

  $enProceso = new Estado();
  $enProceso->nombre = 'En proceso';
  $enProceso->descripcion  = 'Ya se tienen todos los datos del embarque, estamos a la espera de que llegue el mismo para sacarlo de la aduana.';

  $finalizado = new Estado();
  $finalizado->nombre = 'Finalizado';
  $finalizado->descripcion  = 'El embarque ya llego a la bodega y la mercancia ha ingresado al inventario de la bodega';

  $entradaDef->estados()->saveMany([
      $enCaptura,
      $enProceso,
      $finalizado
  ]);
  $entradaDef->estadoInicial_id = $enCaptura->id;
  $entradaDef->save();

  $terminar = new Alfabeto();
  $terminar->nombre = 'Terminar';
  $terminar->descripcion = 'Termina el proceso de forma satisfactoria';
  $guardar = new Alfabeto();
  $guardar->nombre = 'Guardar';
  $guardar->descripcion = 'Guarda la información sin tener que avanzar necesariamente el proceso';
  $packing = new Alfabeto();
  $packing->nombre = 'Packing';
  $packing->descripcion = 'Genera el documento Packing y no avanza el proceso';
  $invoice = new Alfabeto();
  $invoice->nombre = 'Invoice';
  $invoice->descripcion = 'Genera el documento Invoice y no avanza el proceso';
  $rechazar = new Alfabeto();
  $rechazar->nombre = 'Rechazar';
  $rechazar->descripcion = 'No puede terminar el proceso por alguna de las causas';
  $entradaDef->alfabeto()->saveMany([
    $terminar,
    $rechazar,
    $guardar,
    $packing,
    $invoice
  ]);

  $transicion1 = new Transicion();
  $transicion1->nombre = 'En captura a En Proceso';
  $transicion1->descripcion = 'Describe la transicion entre En Captura a En Proceso';
  $transicion1->origen_id = $enCaptura->id;
  $transicion1->destino_id = $enProceso->id;
  $transicion1->token_id = $terminar->id;

  $transicion11 = new Transicion();
  $transicion11->nombre = 'Guarda los datos En captura';
  $transicion11->descripcion = 'Solo guarda los datos';
  $transicion11->origen_id = $enCaptura->id;
  $transicion11->destino_id = $enCaptura->id;
  $transicion11->token_id = $guardar->id;

  $transicion12 = new Transicion();
  $transicion12->nombre = 'Guarda los datos En captura';
  $transicion12->descripcion = 'Solo guarda los datos';
  $transicion12->origen_id = $enCaptura->id;
  $transicion12->destino_id = $enCaptura->id;
  $transicion12->token_id = $packing->id;

  $transicion13 = new Transicion();
  $transicion13->nombre = 'Guarda los datos En captura';
  $transicion13->descripcion = 'Solo guarda los datos';
  $transicion13->origen_id = $enCaptura->id;
  $transicion13->destino_id = $enCaptura->id;
  $transicion13->token_id = $invoice->id;

  $transicion2 = new Transicion();
  $transicion2->nombre = 'En Proceso a Finalizado';
  $transicion2->descripcion = 'Describe la transicion del estado de En Proceso a Finalizado';
  $transicion2->origen_id = $enProceso->id;
  $transicion2->destino_id = $finalizado->id;
  $transicion2->token_id = $terminar->id;

  $transicion21 = new Transicion();
  $transicion21->nombre = 'En Proceso a En Proceso';
  $transicion21->descripcion = 'Solo se ejecuta cuando se guarda la información nuevamente.';
  $transicion21->origen_id = $enProceso->id;
  $transicion21->destino_id = $enProceso->id;
  $transicion21->token_id = $guardar->id;


  $entradaDef->transiciones()->saveMany([
    $transicion1,
    $transicion11,
    $transicion12,
    $transicion13,
    $transicion2,
    $transicion21,
  ]);

  $salidaDef = Automata::create();
  $salidaDef->nombre = 'Salidas';
  $salidaDef->descripcion = 'Ordenes de salida para el envio de mercancias de un contenedor a un proveedor';
  $salidaDef->save();

  // Creamos los estados que vamos a utilizar en el proceso de entrada.
  $enCaptura =  new Estado();
  $enCaptura->nombre = 'En captura';
  $enCaptura->descripcion = 'Estado inicial del flujo de Salidas. Solo se esta capturando la información del WSR.';

  $enProceso = new Estado();
  $enProceso->nombre = 'En proceso';
  $enProceso->descripcion  = 'Ya se tienen todos los datos del embarque, estamos a la espera de que llegue el mismo para sacarlo de la bodega.';

  $finalizado = new Estado();
  $finalizado->nombre = 'Finalizado';
  $finalizado->descripcion  = 'La mercancia ya se entrego al transportista que va a entregarla al destintario';

  $salidaDef->estados()->saveMany([
      $enCaptura,
      $enProceso,
      $finalizado
  ]);
  $salidaDef->estadoInicial_id = $enCaptura->id;
  $salidaDef->save();

  $terminar = new Alfabeto();
  $terminar->nombre = 'Terminar';
  $terminar->descripcion = 'Termina el proceso de forma satisfactoria';
  $guardar = new Alfabeto();
  $guardar->nombre = 'Guardar';
  $guardar->descripcion = 'Guarda la información sin tener que avanzar necesariamente el proceso';
  $rechazar->nombre = 'Rechazar';
  $rechazar->descripcion = 'No puede terminar el proceso por alguna de las causas';
  $salidaDef->alfabeto()->saveMany([
    $terminar,
    $rechazar,
    $guardar
  ]);

  $transicion1 = new Transicion();
  $transicion1->nombre = 'En captura a En Proceso';
  $transicion1->descripcion = 'Describe la transicion entre En Captura a En Proceso';
  $transicion1->origen_id = $enCaptura->id;
  $transicion1->destino_id = $enProceso->id;
  $transicion1->token_id = $terminar->id;

  $transicion11 = new Transicion();
  $transicion11->nombre = 'Guarda los datos En captura';
  $transicion11->descripcion = 'Solo guarda los datos';
  $transicion11->origen_id = $enCaptura->id;
  $transicion11->destino_id = $enCaptura->id;
  $transicion11->token_id = $guardar->id;

  $transicion2 = new Transicion();
  $transicion2->nombre = 'En Proceso a Finalizado';
  $transicion2->descripcion = 'Describe la transicion del estado de En Proceso a Finalizado';
  $transicion2->origen_id = $enProceso->id;
  $transicion2->destino_id = $finalizado->id;
  $transicion2->token_id = $terminar->id;

  $transicion21 = new Transicion();
  $transicion21->nombre = 'En Proceso a En Proceso';
  $transicion21->descripcion = 'Solo se ejecuta cuando se guarda la información nuevamente.';
  $transicion21->origen_id = $enProceso->id;
  $transicion21->destino_id = $enProceso->id;
  $transicion21->token_id = $guardar->id;


  $salidaDef->transiciones()->saveMany([
    $transicion1,
    $transicion11,
    $transicion2,
    $transicion21,
  ]);

}

/**
 * Recibe los comandos a ejecutar.
 */
foreach ($argv as $i=>$arg )
{
    if ( $arg == "create" )
    {
        create();
        die('Se crearon las tablas\n');
    } elseif ( $arg == "drop") {
        drop();
        die('Se eliminaron las tablas\n');
    } elseif ( $arg == "inicialize") {
      inicialize();
      die('Se agregan los valores iniciales');
    }
}
