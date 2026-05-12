<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution;

use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\Money;
use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\OrderId;
use PDO;

/**
 * ShippingService - Handles shipping calculations and management.
 *
 * Extracted from the God Class where shipping logic was mixed with
 * order creation, order modification, and order cancellation.
 *
 * Antipatterns solved:
 * - Mixed Responsibilities: shipping was in OrderManager
 * - Hidden Side Effects: createOrder created shipments as side effect
 * - Magic Numbers: shipping type thresholds (3, 7) and costs
 * - Temporal Coupling: cancelOrder called cancelShipments
 */
final class ShippingService
{
    public function __construct(
        private readonly PDO $db,
        private readonly Config $config,
        private readonly int $storeId,
    ) {}

    public function calculateShipping(int $quantity): ShippingEstimate
    {
        $shipping = $this->config->shipping;

        if ($quantity > $shipping->urgentThreshold) {
            $type = ShippingType::Urgent;
            $days = $shipping->urgentDays;
        } elseif ($quantity > $shipping->expressThreshold) {
            $type = ShippingType::Express;
            $days = $shipping->expressDays;
        } else {
            $type = ShippingType::Standard;
            $days = $shipping->standardDays;
        }

        $cost = new Money($quantity * $shipping->costPerUnit + $shipping->baseCost);

        return new ShippingEstimate(
            type: $type,
            estimatedDays: $days,
            cost: $cost,
        );
    }

    public function createShipment(OrderId $orderId, string $date): int
    {
        $quantity = $this->getOrderQuantity($orderId);
        $estimate = $this->calculateShipping($quantity);

        $sql = 'INSERT INTO shipments 
            (store_id, order_id, shipping_type, estimated_days, shipping_cost, status, carrier, created_at)
            VALUES 
            (:store_id, :order_id, :type, :days, :cost, 1, \'Correos\', :date)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'store_id' => $this->storeId,
            'order_id' => $orderId->value,
            'type' => $estimate->type->value,
            'days' => $estimate->estimatedDays,
            'cost' => $estimate->cost->amount(),
            'date' => $date,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function cancelShipments(OrderId $orderId): void
    {
        $sql = 'UPDATE shipments SET status = 0 WHERE order_id = :order_id AND store_id = :store_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_id' => $orderId->value,
            'store_id' => $this->storeId,
        ]);
    }

    public function getShipments(OrderId $orderId): array
    {
        $sql = 'SELECT * FROM shipments WHERE order_id = :order_id ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId->value]);
        return $stmt->fetchAll();
    }

    private function getOrderQuantity(OrderId $orderId): int
    {
        $sql = 'SELECT quantity FROM orders WHERE id = :id AND store_id = :store_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $orderId->value, 'store_id' => $this->storeId]);
        $row = $stmt->fetch();
        return (int) ($row['quantity'] ?? 1);
    }
}

enum ShippingType: int
{
    case Standard = 1;
    case Express = 2;
    case Urgent = 3;
}

final readonly class ShippingEstimate
{
    public function __construct(
        public ShippingType $type,
        public int $estimatedDays,
        public Money $cost,
    ) {}
}
