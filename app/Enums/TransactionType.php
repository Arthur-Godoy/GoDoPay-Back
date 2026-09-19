<?php

namespace App\Enums;

enum TransactionType: string
{
    case Transfer = 'transfer';
    case Deposit = 'deposit';
}
