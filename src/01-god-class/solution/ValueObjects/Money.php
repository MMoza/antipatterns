<?php

declare(strict_types=1);

namespace AntiPatterns\GodClass\solution\ValueObjects;

/**
 * Money - Value object for monetary amounts.
 *
 * Replaces float/double for money which causes precision issues.
 * Stores amount in cents (integer) internally.
 */
final readonly class Money
{
    public int $cents;

    public function __construct(int|float $amount)
    {
        if (is_float($amount)) {
            $this->cents = (int) round($amount * 100);
        } else {
            $this->cents = $amount * 100;
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents / 100);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function amount(): float
    {
        return $this->cents / 100;
    }

    public function add(self $other): self
    {
        return self::fromCents($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return self::fromCents($this->cents - $other->cents);
    }

    public function multiply(float $factor): self
    {
        return self::fromCents((int) round($this->cents * $factor));
    }

    public function percentage(float $percent): self
    {
        return $this->multiply($percent / 100);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function format(string $symbol = '€'): string
    {
        return $symbol . number_format($this->amount(), 2);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
