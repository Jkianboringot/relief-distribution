<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Claim = 'Claim';
    case UnClaim = 'UnClaim';
    case Pending = 'Pending';
}