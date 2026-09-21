<?php

namespace App\Exceptions;

use Exception;

class SameAccountTransferException extends Exception
{
    public function __construct()
    {
        $this->message = 'Não é possível transferir para a mesma conta';
    }
}
