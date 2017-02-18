<?php

/**
 * Para utilizar este script desde consola ejecutamos
 * php migration.php drop     //Para eliminar tablas
 * php migration.php create   //Para crear las tablas
 *
 * Configurar los datos de conexión a la base de datos.
 */

require __DIR__ . '/../vendor/autoload.php';
if (file_exists(__DIR__ . '/../src/config_override.php')) {
    require __DIR__ . '/../src/config_override.php';
}

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Cartalyst\Sentinel\Native\Facades\Sentinel;

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
        $schema->dropIfExists('notifications');
        $schema->dropIfExists('activations');
        $schema->dropIfExists('persistences');
        $schema->dropIfExists('reminders');
        $schema->dropIfExists('roles');
        $schema->dropIfExists('role_users');
        $schema->dropIfExists('throttle');
        $schema->dropIfExists('users');

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

        $schema->create('notifications', function (Blueprint $table) {
          $table->increments('id');
          $table->string('type');
          $table->string('message');
          $table->string('action');
          $table->date('due');
          $table->unsignedInteger('user_id');

          $table->timestamps();

          $table->engine = 'InnoDB';

          $table->foreign('user_id')
            ->references('id')->on('users');
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

    'configuration'           => false      // Permiso para editar la configuración de la aplicación.
  ];
  $operadorRol->save();
  echo "Se agrega el rol del operador del sistema y sus permisos.<br/>";

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

}

/**
 * Recibe los comandos a ejecutar.
 */
if ($argv[1] == "create" )
{   create();
    print "Se crearon las tablas \n";
    die();
} elseif ( $argv[1] == "drop") {
    drop();
    print 'Se eliminaron las tablas\n';
    die();
} elseif ( $argv[1] == "inicialize") {
  inicialize();
  print 'Se agregan los valores iniciales';
  die();
} else {
    print "\nForma de uso: php migration [opcion]\n";
    print "Opciones:\n";
    print "\tcreate. Crea las tablas basicas\n";
    print "\tdrop. Borra las tablas\n";
    print "\tinicialize. Inicializa con la información\n\n";
    die();
}
