<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution\ValueObjects;

/**
 * ProductId - Typed identifier for products.
 * Replaces raw int IDs that get confused with other entity IDs.
 */
final readonly class ProductId
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('ProductId must be positive');
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
