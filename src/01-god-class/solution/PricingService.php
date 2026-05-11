<?php

declare(strict_types=1);

namespace AntiPatterns\GodClass\solution;

use AntiPatterns\GodClass\solution\ValueObjects\Money;

/**
 * PricingService - Handles all price calculations.
 *
 * Extracted from the God Class where pricing logic was duplicated
 * across createOrder, modifyOrder, generateInvoice, etc.
 *
 * Antipatterns solved:
 * - DRY Violations: discount logic was in 3+ places
 * - Magic Numbers: hardcoded thresholds (3, 7) and percentages (0.05, 0.10)
 * - Float precision: used float for money calculations
 * - Primitive Obsession: tax rates as raw floats
 */
final class PricingService
{
    public function __construct(
        private readonly Config $config,
    ) {}

    public function calculateOrderTotal(
        Money $unitPrice,
        int $quantity,
        string $taxType = 'general',
        string $location = 'peninsula',
    ): PricingResult {
        $subtotal = $unitPrice->multiply($quantity);
        $discount = $this->calculateDiscount($unitPrice, $quantity);
        $subtotalAfterDiscount = $subtotal->subtract($discount);
        $tax = $this->calculateTax($subtotalAfterDiscount, $taxType, $location);
        $total = $subtotalAfterDiscount->add($tax);

        return new PricingResult(
            unitPrice: $unitPrice,
            quantity: $quantity,
            subtotal: $subtotal,
            discount: $discount,
            subtotalAfterDiscount: $subtotalAfterDiscount,
            tax: $tax,
            total: $total,
        );
    }

    public function calculateDiscount(Money $unitPrice, int $quantity): Money
    {
        $subtotal = $unitPrice->multiply($quantity);
        $discounts = $this->config->discounts;

        if ($quantity >= $discounts->bulkThreshold) {
            return $subtotal->percentage($discounts->bulkPercentage * 100);
        }

        if ($quantity >= $discounts->mediumThreshold) {
            return $subtotal->percentage($discounts->mediumPercentage * 100);
        }

        return Money::zero();
    }

    public function calculateTax(Money $base, string $type = 'general', string $location = 'peninsula'): Money
    {
        $rate = $this->config->taxes->getRate($type, $location);
        return $base->multiply($rate);
    }

    public function applyCoupon(Money $total, Coupon $coupon): Money
    {
        if (!$coupon->isValid()) {
            return $total;
        }

        $discount = match ($coupon->type) {
            'percentage' => $total->percentage($coupon->value),
            'fixed' => new Money($coupon->value),
        };

        $newTotal = $total->subtract($discount);

        return $newTotal->cents > 0 ? $newTotal : Money::zero();
    }

    public function calculateRefund(Money $orderTotal, int $daysSinceOrder): Money
    {
        $percentage = match (true) {
            $daysSinceOrder < 2 => 100,
            $daysSinceOrder < 7 => 50,
            $daysSinceOrder < 14 => 25,
            default => 0,
        };

        return $orderTotal->percentage($percentage);
    }
}

final readonly class PricingResult
{
    public function __construct(
        public Money $unitPrice,
        public int $quantity,
        public Money $subtotal,
        public Money $discount,
        public Money $subtotalAfterDiscount,
        public Money $tax,
        public Money $total,
    ) {}
}

final readonly class Coupon
{
    public function __construct(
        public string $code,
        public string $type,
        public float $value,
        public ?string $validFrom = null,
        public ?string $validUntil = null,
    ) {}

    public function isValid(): bool
    {
        $now = date('Y-m-d');
        if ($this->validFrom !== null && $now < $this->validFrom) {
            return false;
        }
        if ($this->validUntil !== null && $now > $this->validUntil) {
            return false;
        }
        return true;
    }
}
