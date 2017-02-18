<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class FraccionValidationException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Las partidas de la orden deben de tener Fracción Arancelaria.',
        ],
    ];
}
