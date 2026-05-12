<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

enum ReservationStatus: int
{
    case Pending = 1;
    case Confirmed = 2;
    case Shipped = 3;
    case Completed = 4;
    case Cancelled = 5;
}
