<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Claim = 'claimed';
    case UnClaim = 'unclaim';
    case Pending = 'pending';
}