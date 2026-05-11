<?php

declare(strict_types=1);

namespace AntiPatterns\GodClass\solution;

use AntiPatterns\GodClass\solution\ValueObjects\CustomerId;
use PDO;

/**
 * CustomerService - Handles customer management operations.
 *
 * Extracted from the God Class where customer logic was mixed with
 * order management, shipping, and pricing.
 *
 * Antipatterns solved:
 * - God Class: customer management was part of OrderManager
 * - Mutable Shared State: $this->customerData was modified everywhere
 * - SQL Injection: raw queries with interpolated variables
 * - Silent Catch: errors swallowed in try/catch blocks
 */
final class CustomerService
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    public function findById(CustomerId $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id');
        $stmt->execute(['id' => $id->value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getOrderHistory(CustomerId $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, store_id, created_at, total_amount FROM orders WHERE customer_id = :id ORDER BY created_at DESC'
        );
        $stmt->execute(['id' => $id->value]);
        return $stmt->fetchAll();
    }

    public function getCustomerWithHistory(CustomerId $id): ?CustomerDetail
    {
        $customer = $this->findById($id);
        if ($customer === null) {
            return null;
        }

        $orders = $this->getOrderHistory($id);
        $totalSpent = 0.0;
        foreach ($orders as $order) {
            $totalSpent += (float) $order['total_amount'];
        }

        return new CustomerDetail(
            id: new CustomerId((int) $customer['id']),
            name: $customer['name'],
            email: $customer['email'] ?? null,
            phone: $customer['phone'] ?? null,
            totalOrders: count($orders),
            totalSpent: $totalSpent,
            orders: $orders,
        );
    }

    public function mergeCustomers(CustomerId $sourceId, CustomerId $targetId): Result
    {
        if ($sourceId->value === $targetId->value) {
            return Result::failure('Cannot merge a customer with itself');
        }

        $source = $this->findById($sourceId);
        $target = $this->findById($targetId);

        if ($source === null || $target === null) {
            return Result::failure('Customer not found');
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                'UPDATE orders SET customer_id = :target WHERE customer_id = :source'
            )->execute(['target' => $targetId->value, 'source' => $sourceId->value]);

            $updates = [];
            $params = ['id' => $targetId->value];

            if (empty($target['email']) && !empty($source['email'])) {
                $updates[] = 'email = :email';
                $params['email'] = $source['email'];
            }
            if (empty($target['phone']) && !empty($source['phone'])) {
                $updates[] = 'phone = :phone';
                $params['phone'] = $source['phone'];
            }

            if (!empty($updates)) {
                $sql = 'UPDATE customers SET ' . implode(', ', $updates) . ' WHERE id = :id';
                $this->db->prepare($sql)->execute($params);
            }

            $this->db->prepare(
                'UPDATE customers SET active = 0, merged_to = :target WHERE id = :source'
            )->execute(['target' => $targetId->value, 'source' => $sourceId->value]);

            $this->db->commit();

            return Result::success([
                'merged_customer_id' => $targetId->value,
                'removed_customer_id' => $sourceId->value,
            ]);
        } catch (\Exception $e) {
            $this->db->rollBack();
            return Result::failure('Failed to merge customers: ' . $e->getMessage());
        }
    }
}

final readonly class CustomerDetail
{
    public function __construct(
        public CustomerId $id,
        public string $name,
        public ?string $email,
        public ?string $phone,
        public int $totalOrders,
        public float $totalSpent,
        public array $orders,
    ) {}
}
