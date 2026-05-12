<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

final readonly class PricingResult
{
    public function __construct(
        public float $base,
        public float $taxes,
        public float $total,
    ) {}
}

final readonly class PricingCalculator
{
    public function __construct(
        private readonly float $taxRate = 0.21,
    ) {}

    public function calculate(Reservation $reservation): PricingResult
    {
        $base = $reservation->baseTotal();
        $taxes = $base * $this->taxRate;

        return new PricingResult(
            base: $base,
            taxes: $taxes,
            total: $base + $taxes,
        );
    }
}
