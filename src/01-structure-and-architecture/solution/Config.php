<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution;

/**
 * Config - Externalized configuration
 *
 * Replaces hardcoded values, magic numbers, and scattered config loading.
 * All configuration is explicit, typed, and injectable.
 */
final readonly class Config
{
    public function __construct(
        public int $storeId,
        public TaxConfig $taxes,
        public DiscountConfig $discounts,
        public ShippingConfig $shipping,
        public bool $debugMode = false,
        public string $currencySymbol = '€',
        public string $currencyCode = 'EUR',
    ) {}

    public static function default(int $storeId = 1): self
    {
        return new self(
            storeId: $storeId,
            taxes: TaxConfig::default(),
            discounts: DiscountConfig::default(),
            shipping: ShippingConfig::default(),
        );
    }
}

final readonly class TaxConfig
{
    public function __construct(
        public float $general = 0.21,
        public float $reduced = 0.10,
        public float $superReduced = 0.04,
        public float $canariasIgic = 0.07,
    ) {}

    public static function default(): self
    {
        return new self();
    }

    public function getRate(string $type, string $location = 'peninsula'): float
    {
        return match ($location) {
            'canarias' => $this->canariasIgic,
            'ceuta_melilla' => $this->superReduced,
            default => match ($type) {
                'reduced' => $this->reduced,
                'super_reduced' => $this->superReduced,
                default => $this->general,
            },
        };
    }
}

final readonly class DiscountConfig
{
    public function __construct(
        public int $bulkThreshold = 7,
        public float $bulkPercentage = 0.10,
        public int $mediumThreshold = 3,
        public float $mediumPercentage = 0.05,
    ) {}

    public static function default(): self
    {
        return new self();
    }
}

final readonly class ShippingConfig
{
    public function __construct(
        public int $urgentThreshold = 7,
        public int $expressThreshold = 3,
        public float $costPerUnit = 2.5,
        public float $baseCost = 5.0,
        public int $urgentDays = 1,
        public int $expressDays = 3,
        public int $standardDays = 5,
    ) {}

    public static function default(): self
    {
        return new self();
    }
}
