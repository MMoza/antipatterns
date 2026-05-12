<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

use PDO;

/**
 * ReservationFactory - Creates Reservation objects from database.
 *
 * Replaces the implicit loadReservation() that mutated shared state.
 * Factory is explicit: call it, get a Reservation, no side effects.
 */
final class ReservationFactory
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    public function findById(int $id): ?Reservation
    {
        $stmt = $this->db->prepare('SELECT * FROM reservations WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new Reservation(
            id: (int) $row['id'],
            customerId: (int) $row['customer_id'],
            storeId: (int) $row['store_id'],
            basePrice: (float) $row['base_price'],
            nights: (int) $row['nights'],
            weight: (float) ($row['weight'] ?? 0),
            destination: $row['destination'] ?? '',
            status: ReservationStatus::from((int) $row['status']),
            paymentStatus: PaymentStatus::from((int) $row['payment_status']),
            transactionId: $row['transaction_id'] ?: null,
            couponCode: $row['coupon_code'] ?: null,
            createdAt: $row['created_at'],
        );
    }
}
