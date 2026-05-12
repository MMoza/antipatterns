<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

enum PaymentStatus: int
{
    case Unpaid = 0;
    case Paid = 1;
    case Refunded = 2;
    case Failed = 3;
}
