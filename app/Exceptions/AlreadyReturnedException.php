<?php

namespace App\Exceptions;

use Exception;

class AlreadyReturnedException extends Exception
{
    public function __construct()
    {
        $this->message = 'Transação não pode ser Revertida';
    }
}
