<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

final readonly class Coupon
{
    private const VALID = [
        'SUMMER20' => ['type' => 'percent', 'value' => 20],
        'WINTER10' => ['type' => 'percent', 'value' => 10],
        'FLAT50' => ['type' => 'fixed', 'value' => 50],
    ];

    public function __construct(
        public string $code,
        public string $type,
        public float $value,
    ) {}

    public static function fromCode(string $code): ?self
    {
        if (!isset(self::VALID[$code])) {
            return null;
        }

        $data = self::VALID[$code];
        return new self($code, $data['type'], $data['value']);
    }

    public function apply(float $total): float
    {
        return match ($this->type) {
            'percent' => $total * ($this->value / 100),
            'fixed' => min($this->value, $total),
            default => 0,
        };
    }
}
