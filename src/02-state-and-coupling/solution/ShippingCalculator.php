<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

/**
 * ShippingCalculator - Pure function for shipping cost calculation.
 *
 * Replaces the environment-dependent calculateShipping() method
 * that read from mutable shared state and had hidden side effects.
 */
final class ShippingCalculator
{
    public function __construct(
        private readonly ShippingConfig $config,
    ) {}

    public function calculate(Reservation $reservation): ShippingEstimate
    {
        if ($reservation->isShipped()) {
            return new ShippingEstimate(
                cost: 0,
                method: ShippingMethod::AlreadyShipped,
                estimatedDays: 0,
                note: 'Already shipped',
            );
        }

        $method = $this->determineMethod($reservation->weight);
        $cost = $this->calculateCost($reservation->weight, $method);

        return new ShippingEstimate(
            cost: $cost,
            method: $method,
            estimatedDays: $this->getEstimatedDays($method),
        );
    }

    private function determineMethod(float $weight): ShippingMethod
    {
        if ($weight > $this->config->freightThreshold) {
            return ShippingMethod::Freight;
        }
        if ($weight > $this->config->expressThreshold) {
            return ShippingMethod::Express;
        }
        return ShippingMethod::Standard;
    }

    private function calculateCost(float $weight, ShippingMethod $method): float
    {
        return match ($method) {
            ShippingMethod::Freight => $weight * $this->config->freightRate + $this->config->freightBase,
            ShippingMethod::Express => $weight * $this->config->expressRate + $this->config->expressBase,
            ShippingMethod::Standard => $weight * $this->config->standardRate + $this->config->standardBase,
            ShippingMethod::AlreadyShipped => 0,
        };
    }

    private function getEstimatedDays(ShippingMethod $method): int
    {
        return match ($method) {
            ShippingMethod::Freight => $this->config->freightDays,
            ShippingMethod::Express => $this->config->expressDays,
            ShippingMethod::Standard => $this->config->standardDays,
            ShippingMethod::AlreadyShipped => 0,
        };
    }
}

enum ShippingMethod: string
{
    case Standard = 'standard';
    case Express = 'express';
    case Freight = 'freight';
    case AlreadyShipped = 'already_shipped';
}

final readonly class ShippingEstimate
{
    public function __construct(
        public float $cost,
        public ShippingMethod $method,
        public int $estimatedDays,
        public ?string $note = null,
    ) {}
}

final readonly class ShippingConfig
{
    public function __construct(
        public float $standardRate = 2.5,
        public float $standardBase = 5.0,
        public int $standardDays = 5,
        public float $expressRate = 4.0,
        public float $expressBase = 10.0,
        public int $expressDays = 2,
        public float $freightRate = 6.0,
        public float $freightBase = 20.0,
        public int $freightDays = 7,
        public float $expressThreshold = 2.0,
        public float $freightThreshold = 10.0,
    ) {}
}
