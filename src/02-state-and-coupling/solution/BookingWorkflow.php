<?php

declare(strict_types=1);

namespace AntiPatterns\StateAndCoupling\solution;

use PDO;

/**
 * BookingWorkflow - Explicit, step-by-step reservation workflow.
 *
 * Replaces the implicit workflow of ReservationContext where:
 * - Methods had to be called in a specific order (temporal coupling)
 * - State was shared mutably between steps
 * - Side effects were hidden in "getter" methods
 * - Environment logic was scattered everywhere
 *
 * This workflow is explicit:
 * - Each step takes input and returns output
 * - No mutable shared state between steps
 * - Side effects are explicit (database updates are separate operations)
 * - Environment configuration is injected, not detected
 */
final class BookingWorkflow
{
    public function __construct(
        private readonly ReservationFactory $factory,
        private readonly ShippingCalculator $shipping,
        private readonly PricingCalculator $pricing,
        private readonly StatusTransition $transitions,
        private readonly PDO $db,
    ) {}

    public function confirmReservation(int $reservationId): WorkflowResult
    {
        $reservation = $this->factory->findById($reservationId);
        if ($reservation === null) {
            return WorkflowResult::failure('Reservation not found');
        }

        try {
            $this->transitions->assertCanTransition($reservation->status, ReservationStatus::Confirmed);
        } catch (\InvalidArgumentException $e) {
            return WorkflowResult::failure($e->getMessage());
        }

        $pricing = $this->pricing->calculate($reservation);
        $shippingEstimate = $this->shipping->calculate($reservation);

        $total = $pricing->total + $shippingEstimate->cost;

        $updated = $reservation->withStatus(ReservationStatus::Confirmed);

        $this->updateReservation($updated, $total);

        return WorkflowResult::success(new ConfirmedReservation(
            reservation: $updated,
            pricing: $pricing,
            shipping: $shippingEstimate,
            total: $total,
        ));
    }

    public function applyCoupon(int $reservationId, string $couponCode): WorkflowResult
    {
        $reservation = $this->factory->findById($reservationId);
        if ($reservation === null) {
            return WorkflowResult::failure('Reservation not found');
        }

        $coupon = Coupon::fromCode($couponCode);
        if ($coupon === null) {
            return WorkflowResult::failure('Invalid coupon code');
        }

        $pricing = $this->pricing->calculate($reservation);
        $discount = $coupon->apply($pricing->total);

        $this->db->prepare(
            'UPDATE reservations SET coupon_code = :code, discount = :discount WHERE id = :id'
        )->execute([
            'code' => $couponCode,
            'discount' => $discount,
            'id' => $reservationId,
        ]);

        return WorkflowResult::success(new CouponApplied(
            coupon: $coupon,
            discount: $discount,
            newTotal: $pricing->total - $discount,
        ));
    }

    public function processPayment(int $reservationId, string $paymentMethod): WorkflowResult
    {
        $reservation = $this->factory->findById($reservationId);
        if ($reservation === null) {
            return WorkflowResult::failure('Reservation not found');
        }

        if ($reservation->isPaid()) {
            return WorkflowResult::failure('Reservation already paid');
        }

        $pricing = $this->pricing->calculate($reservation);
        $shippingEstimate = $this->shipping->calculate($reservation);
        $total = $pricing->total + $shippingEstimate->cost;

        if ($reservation->couponCode !== null) {
            $coupon = Coupon::fromCode($reservation->couponCode);
            if ($coupon !== null) {
                $total -= $coupon->apply($pricing->total);
            }
        }

        $transactionId = 'txn-' . uniqid();

        $updated = $reservation->withPayment($transactionId);
        $this->updateReservation($updated, $total);

        return WorkflowResult::success(new PaymentProcessed(
            transactionId: $transactionId,
            amount: $total,
            reservation: $updated,
        ));
    }

    private function updateReservation(Reservation $reservation, float $total): void
    {
        $this->db->prepare(
            'UPDATE reservations SET status = :status, payment_status = :payment, 
             transaction_id = :tx, total = :total WHERE id = :id'
        )->execute([
            'status' => $reservation->status->value,
            'payment' => $reservation->paymentStatus->value,
            'tx' => $reservation->transactionId,
            'total' => $total,
            'id' => $reservation->id,
        ]);
    }
}

final readonly class WorkflowResult
{
    public function __construct(
        public bool $success,
        public mixed $data,
        public ?string $error,
    ) {}

    public static function success(mixed $data): self
    {
        return new self(true, $data, null);
    }

    public static function failure(string $error): self
    {
        return new self(false, null, $error);
    }
}

final readonly class ConfirmedReservation
{
    public function __construct(
        public Reservation $reservation,
        public PricingResult $pricing,
        public ShippingEstimate $shipping,
        public float $total,
    ) {}
}

final readonly class CouponApplied
{
    public function __construct(
        public Coupon $coupon,
        public float $discount,
        public float $newTotal,
    ) {}
}

final readonly class PaymentProcessed
{
    public function __construct(
        public string $transactionId,
        public float $amount,
        public Reservation $reservation,
    ) {}
}
