<?php

namespace App\Enums;

enum ApprovalStatus: int
{
    case PENDING = 1;
    case APPROVED = 2;
}
