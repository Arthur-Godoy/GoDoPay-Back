<?php

namespace App\Exceptions;

use Exception;

class NotAccountOwnerException extends Exception
{
    public function __construct()
    {
        $this->message = 'Você não tem acesso a esta conta';
    }
}
