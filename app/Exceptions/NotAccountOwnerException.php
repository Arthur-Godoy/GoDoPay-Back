<?php

namespace App\Exceptions;

use Exception;

class NotAccountOwnerException extends Exception
{
    public function __construct() {
        $this->message = "You don`t have access to this account";
    }
}
