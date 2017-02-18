<?php

namespace App\Validation;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException;
use Slim\Http\Request;

class Validator
{
  public $errores = [];
  protected $messages = [];

  public function __construct()
  {
      //$this->initRules();
      $this->initMessages();
  }

  public function validate(Request $request, array $rules)
  {
      foreach ($rules as $field => $rule) {
          try {
              if (!$rule->getName()) {
                  $rule->setName(ucfirst($field))->assert($request->getParam($field));
              } else {
                  $rule->assert($request->getParam($field));
              }
          } catch (NestedValidationException $e) {
              $this->errores[$field] = $e->findMessages($this->messages);
          }
      }
      $_SESSION['errors'] = $this->errores;
      return $this;
    }

    public function failed()
    {
        return !empty($this->errores);
    }

    public function initMessages()
    {
      $this->messages = [
        'alpha'                     => '{{name}} solo admite caracteres alfabéticos.',
        'alnum'                     => '{{name}} solo admite caracteres alfanuméricos y guiones.',
        'numeric'                   => '{{name}} solo admite caracteres numéricos.',
        'noWhitespace'              => '{{name}} no debe contener espacios en blanco.',
        'notEmpty'                  => '{{name}} es un dato requerido',
        'length'                    => '{{name}} la longitud permitida es de {{minValue}} hasta {{maxValue}} caracteres.',
        'email'                     => 'Por favor asegúrese de ingresar un correo electrónico válido.',
        'phone'                     => 'Por favor asegúrese de ingresar un teléfono válido.',
        'date'                      => 'Por favor asegúrese de ingresar una fecha válida para {{name}} ({{format}}).',
        'identical'                 => 'La confirmación no coincide.',
        'password_confirmation'     => 'La confirmación de la contraseña no coincide.',
        'matchesPassword'           => 'La confirmación de la contraseña no coincide.',
        'notDuplicatedKey'          => '{{name}} ya existe.',
        'regex'                     => 'La contraseña debe contener al menos una letra mayúscula, un símbolo y un número.',
        'max'                       => '{{name}} debe ser menor o igual que {{interval}}',
        'min'                       => '{{name}} debe ser mayor o igual que {{interval}}',
        'emailAvailable'            => '{{name}} ya esta registrado',
        'umtValidation'             => 'Todas las partidas de la {{name}} deben de tener informado el UMT',
        'fraccionValidation'        => 'Todas las partidas de la {{name}} deben de tener informado la fracción arancelaria',
        'entradaPartidasValidation' => 'La orden debe de tener almenos una partida',
        'oneOrAnotherValidation'    => 'Debe de estar presente almenos un valor para {{ name }} {{ other }}',
      ];
    }

}
