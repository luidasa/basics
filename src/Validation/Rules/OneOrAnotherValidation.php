<?php

namespace App\Validation\Rules;

use \Respect\Validation\Rules\AbstractRule;

class OneOrAnotherValidation extends AbstractRule
{
  private $another;

  public function __construct($another)
  {
      $this->another = $another;
  }

  public function validate($input)
  {
    return false;
    //return ((strlen($input) === 0) || (strlen($this->another) === 0));
  }
}
