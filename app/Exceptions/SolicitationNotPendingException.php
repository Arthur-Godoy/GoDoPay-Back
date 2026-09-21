<?php

namespace App\Exceptions;

use Exception;

class SolicitationNotPendingException extends Exception
{
    public function __construct()
    {
        $this->message = 'Esta solicitação de devolução já foi respondida';
    }
}
