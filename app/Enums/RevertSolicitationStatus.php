<?php

namespace App\Enums;

enum RevertSolicitationStatus: string
{
    case Approved = 'approved';
    case Pending = 'pending';
    case Refused = 'refused';
}
