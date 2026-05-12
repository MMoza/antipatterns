<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution\ValueObjects;

/**
 * CustomerId - Typed identifier for customers.
 * Replaces raw int IDs that get confused with other entity IDs.
 */
final readonly class CustomerId
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('CustomerId must be positive');
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
