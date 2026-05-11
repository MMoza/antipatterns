<?php

declare(strict_types=1);

namespace AntiPatterns\GodClass\solution;

use AntiPatterns\GodClass\solution\ValueObjects\Money;
use AntiPatterns\GodClass\solution\ValueObjects\OrderId;
use AntiPatterns\GodClass\solution\ValueObjects\ProductId;
use PDO;

/**
 * OrderRepository - Data access abstraction for orders.
 *
 * Replaces inline SQL queries scattered throughout the God Class.
 * All database access is centralized here with prepared statements.
 */
final class OrderRepository
{
    public function __construct(
        private readonly PDO $db,
        private readonly int $storeId,
    ) {}

    public function findById(OrderId $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM orders WHERE id = :id AND store_id = :store_id'
        );
        $stmt->execute(['id' => $id->value, 'store_id' => $this->storeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(array $filters = []): array
    {
        $sql = 'SELECT * FROM orders WHERE store_id = :store_id';
        $params = ['store_id' => $this->storeId];

        if (!empty($filters['status'])) {
            $sql .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['product_id'])) {
            $sql .= ' AND product_id = :product_id';
            $params['product_id'] = $filters['product_id'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= ' AND created_at >= :start_date';
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= ' AND created_at <= :end_date';
            $params['end_date'] = $filters['end_date'];
        }

        $sql .= ' ORDER BY created_at DESC LIMIT 1000';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): OrderId
    {
        $sql = 'INSERT INTO orders 
            (store_id, product_id, customer_name, customer_email, customer_phone,
             quantity, unit_price, subtotal, discount, tax, total,
             status, is_vip, created_at)
            VALUES 
            (:store_id, :product_id, :customer_name, :customer_email, :customer_phone,
             :quantity, :unit_price, :subtotal, :discount, :tax, :total,
             :status, :is_vip, datetime(\'now\'))';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'store_id' => $this->storeId,
            'product_id' => $data['product_id'],
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'],
            'subtotal' => $data['subtotal'],
            'discount' => $data['discount'],
            'tax' => $data['tax'],
            'total' => $data['total'],
            'status' => $data['status'] ?? OrderStatus::Pending->value,
            'is_vip' => $data['is_vip'] ?? 0,
        ]);

        return new OrderId((int) $this->db->lastInsertId());
    }

    public function update(OrderId $id, array $data): void
    {
        $allowed = ['quantity', 'customer_name', 'status', 'subtotal', 'discount', 'tax', 'total'];
        $updates = [];
        $params = ['id' => $id->value, 'store_id' => $this->storeId];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($updates)) {
            return;
        }

        $sql = 'UPDATE orders SET ' . implode(', ', $updates) . ' WHERE id = :id AND store_id = :store_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function cancel(OrderId $id, string $reason, Money $refundAmount): void
    {
        $sql = 'UPDATE orders SET status = :status, cancellation_reason = :reason,
                refund_amount = :refund, cancelled_at = datetime(\'now\')
                WHERE id = :id AND store_id = :store_id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => OrderStatus::Cancelled->value,
            'reason' => $reason,
            'refund' => $refundAmount->amount(),
            'id' => $id->value,
            'store_id' => $this->storeId,
        ]);
    }

    public function countByStatus(int $status): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM orders WHERE store_id = :store_id AND status = :status'
        );
        $stmt->execute(['store_id' => $this->storeId, 'status' => $status]);
        return (int) $stmt->fetchColumn();
    }

    public function getProductPrice(ProductId $productId): Money
    {
        $stmt = $this->db->prepare(
            'SELECT price FROM products WHERE id = :id AND store_id = :store_id'
        );
        $stmt->execute(['id' => $productId->value, 'store_id' => $this->storeId]);
        $row = $stmt->fetch();
        return new Money($row['price'] ?? 0);
    }

    public function getAvailableStock(ProductId $productId): int
    {
        $stmt = $this->db->prepare(
            'SELECT stock - reserved_stock as available FROM products WHERE id = :id AND store_id = :store_id'
        );
        $stmt->execute(['id' => $productId->value, 'store_id' => $this->storeId]);
        $row = $stmt->fetch();
        return (int) ($row['available'] ?? 0);
    }
}
