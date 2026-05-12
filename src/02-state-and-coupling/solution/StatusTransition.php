<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

/**
 * StatusTransition - Explicit state machine for reservation status changes.
 *
 * Replaces implicit status transitions scattered across methods.
 * Every transition is explicit, validated, and documented.
 */
final class StatusTransition
{
    private const ALLOWED_TRANSITIONS = [
        1 => [2, 5],
        2 => [3, 5],
        3 => [4],
        4 => [],
        5 => [],
    ];

    public function canTransition(ReservationStatus $from, ReservationStatus $to): bool
    {
        return in_array($to->value, self::ALLOWED_TRANSITIONS[$from->value], true);
    }

    public function assertCanTransition(ReservationStatus $from, ReservationStatus $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$from->name} to {$to->name}"
            );
        }
    }

    public function getAvailableTransitions(ReservationStatus $current): array
    {
        return array_map(
            fn(int $v) => ReservationStatus::from($v),
            self::ALLOWED_TRANSITIONS[$current->value]
        );
    }
}
