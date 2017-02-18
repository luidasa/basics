<?php

namespace App\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class OneOrAnotherValidationException extends ValidationException
{
    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'Uno de los dos valores debe de estar presente.',
        ],
    ];
}
