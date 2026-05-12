<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

/**
 * Reservation - Immutable value object representing a reservation.
 *
 * Replaces the mutable $this->currentReservation + $this->context pattern.
 * All data is passed explicitly, no shared mutable state.
 */
final readonly class Reservation
{
    public function __construct(
        public int $id,
        public int $customerId,
        public int $storeId,
        public float $basePrice,
        public int $nights,
        public float $weight,
        public string $destination,
        public ReservationStatus $status,
        public PaymentStatus $paymentStatus,
        public ?string $transactionId,
        public ?string $couponCode,
        public string $createdAt,
    ) {}

    public function baseTotal(): float
    {
        return $this->basePrice * $this->nights;
    }

    public function isShipped(): bool
    {
        return $this->status->value >= 3;
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === PaymentStatus::Paid;
    }

    public function withStatus(ReservationStatus $status): self
    {
        return new self(
            id: $this->id,
            customerId: $this->customerId,
            storeId: $this->storeId,
            basePrice: $this->basePrice,
            nights: $this->nights,
            weight: $this->weight,
            destination: $this->destination,
            status: $status,
            paymentStatus: $this->paymentStatus,
            transactionId: $this->transactionId,
            couponCode: $this->couponCode,
            createdAt: $this->createdAt,
        );
    }

    public function withPayment(string $transactionId): self
    {
        return new self(
            id: $this->id,
            customerId: $this->customerId,
            storeId: $this->storeId,
            basePrice: $this->basePrice,
            nights: $this->nights,
            weight: $this->weight,
            destination: $this->destination,
            status: $this->status,
            paymentStatus: PaymentStatus::Paid,
            transactionId: $transactionId,
            couponCode: $this->couponCode,
            createdAt: $this->createdAt,
        );
    }
}

enum PaymentStatus: int
{
    case Unpaid = 0;
    case Paid = 1;
    case Refunded = 2;
    case Failed = 3;
}
