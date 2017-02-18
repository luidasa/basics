<?php

namespace App\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;

class MatchesPassword extends AbstractRule
{
    private $password;

    public function __construct($password)
    {
        $this->password = $password;
    }

    public function validate($input)
    {
        return password_verify($input, $this->password);
    }
}
