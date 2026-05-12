<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution;

/**
 * OrderStatus - Replaces magic integer status codes.
 *
 * Antipattern: status = 1, 2, 3, 4, 5 with no documentation
 * Solution: Typed enum with explicit names and labels
 */
enum OrderStatus: int
{
    case Pending = 1;
    case Processing = 2;
    case Shipped = 3;
    case Cancelled = 4;
    case Returned = 5;

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Processing => 'Procesando',
            self::Shipped => 'Enviado',
            self::Cancelled => 'Cancelado',
            self::Returned => 'Devuelto',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#28a745',
            self::Processing => '#ffc107',
            self::Shipped => '#17a2b8',
            self::Cancelled => '#dc3545',
            self::Returned => '#6c757d',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }
}
