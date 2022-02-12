<?php

namespace App\Enums;

enum ReservationStatus: int
{
    case ACTIVE = 1;
    case CANCELLED = 2;
}
