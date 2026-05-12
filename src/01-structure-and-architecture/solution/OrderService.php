<?php

declare(strict_types=1);

namespace AntiPatterns\StructureAndArchitecture\solution;

use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\CustomerId;
use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\Money;
use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\OrderId;
use AntiPatterns\StructureAndArchitecture\solution\ValueObjects\ProductId;
use DateTimeImmutable;

/**
 * OrderService - Orchestrates order business logic.
 *
 * Extracted from the God Class. This service coordinates with
 * OrderRepository, PricingService, and ShippingService but does NOT
 * handle pricing calculations, shipping logic, or customer management directly.
 *
 * Antipatterns solved:
 * - God Class: single responsibility - only order lifecycle
 * - Constructor Heavy: lightweight constructor with explicit dependencies
 * - Mutable Shared State: no $this->currentOrder, all data passed explicitly
 * - Temporal Coupling: no implicit state between method calls
 * - Hidden Side Effects: explicit about what it does
 * - Presentation Mixed with Domain: returns Result objects, not HTML
 * - SQL Injection: delegates to repository with prepared statements
 * - Leaky Abstractions: clear interface, no internal state exposed
 * - Hardcoded Infrastructure: all config via Config object
 * - Infrastructure Leakage: no SQL in business logic
 */
final class OrderService
{
    public function __construct(
        private readonly OrderRepository $repository,
        private readonly PricingService $pricing,
        private readonly ShippingService $shipping,
    ) {}

    public function createOrder(CreateOrderRequest $request): Result
    {
        $unitPrice = $this->repository->getProductPrice($request->productId);
        $availableStock = $this->repository->getAvailableStock($request->productId);

        if ($availableStock < $request->quantity) {
            return Result::failure('Insufficient stock');
        }

        $pricingResult = $this->pricing->calculateOrderTotal(
            unitPrice: $unitPrice,
            quantity: $request->quantity,
            taxType: $request->taxType,
            location: $request->location,
        );

        $isVip = $pricingResult->total->isGreaterThan(new Money(5000));

        $orderId = $this->repository->create([
            'product_id' => $request->productId->value,
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'customer_phone' => $request->customerPhone,
            'quantity' => $request->quantity,
            'unit_price' => $unitPrice->amount(),
            'subtotal' => $pricingResult->subtotal->amount(),
            'discount' => $pricingResult->discount->amount(),
            'tax' => $pricingResult->tax->amount(),
            'total' => $pricingResult->total->amount(),
            'status' => OrderStatus::Pending->value,
            'is_vip' => $isVip ? 1 : 0,
        ]);

        $this->shipping->createShipment($orderId, date('Y-m-d'));

        return Result::success(new OrderCreatedResponse(
            orderId: $orderId,
            pricing: $pricingResult,
            isVip: $isVip,
        ));
    }

    public function getOrder(OrderId $id): Result
    {
        $order = $this->repository->findById($id);
        if ($order === null) {
            return Result::failure('Order not found');
        }

        $status = OrderStatus::from($order['status']);
        $shipments = $this->shipping->getShipments($id);

        return Result::success(new OrderDetail(
            id: new OrderId((int) $order['id']),
            customerName: $order['customer_name'],
            customerEmail: $order['customer_email'],
            quantity: (int) $order['quantity'],
            unitPrice: new Money($order['unit_price']),
            subtotal: new Money($order['subtotal']),
            discount: new Money($order['discount']),
            tax: new Money($order['tax']),
            total: new Money($order['total']),
            status: $status,
            isVip: (bool) $order['is_vip'],
            createdAt: $order['created_at'],
            shipments: $shipments,
        ));
    }

    public function listOrders(array $filters = []): Result
    {
        $orders = $this->repository->findAll($filters);

        $details = [];
        foreach ($orders as $order) {
            $details[] = new OrderSummary(
                id: new OrderId((int) $order['id']),
                customerName: $order['customer_name'],
                total: new Money($order['total']),
                status: OrderStatus::from($order['status']),
                createdAt: $order['created_at'],
            );
        }

        return Result::success($details);
    }

    public function modifyOrder(ModifyOrderRequest $request): Result
    {
        $order = $this->repository->findById($request->orderId);
        if ($order === null) {
            return Result::failure('Order not found');
        }

        $quantity = $request->quantity ?? (int) $order['quantity'];
        $unitPrice = new Money($order['unit_price']);
        $pricingResult = $this->pricing->calculateOrderTotal(
            unitPrice: $unitPrice,
            quantity: $quantity,
        );

        $updateData = [
            'quantity' => $quantity,
            'subtotal' => $pricingResult->subtotal->amount(),
            'discount' => $pricingResult->discount->amount(),
            'tax' => $pricingResult->tax->amount(),
            'total' => $pricingResult->total->amount(),
        ];

        if ($request->customerName !== null) {
            $updateData['customer_name'] = $request->customerName;
        }
        if ($request->status !== null) {
            $updateData['status'] = $request->status->value;
        }

        $this->repository->update($request->orderId, $updateData);

        if ($request->quantity !== null) {
            $this->shipping->createShipment($request->orderId, date('Y-m-d'));
        }

        return Result::success(new OrderModifiedResponse(
            orderId: $request->orderId,
            newTotal: $pricingResult->total,
            priceDifference: $pricingResult->total->subtract(new Money($order['total'])),
        ));
    }

    public function cancelOrder(CancelOrderRequest $request): Result
    {
        $order = $this->repository->findById($request->orderId);
        if ($order === null) {
            return Result::failure('Order not found');
        }

        $status = OrderStatus::from($order['status']);
        if (!$status->isCancellable()) {
            return Result::failure('Order cannot be cancelled in current status');
        }

        $orderDate = new DateTimeImmutable($order['created_at']);
        $now = new DateTimeImmutable();
        $daysSinceOrder = (int) $now->diff($orderDate)->format('%r%a');

        $refundAmount = $this->pricing->calculateRefund(
            new Money($order['total']),
            $daysSinceOrder,
        );

        $this->repository->cancel($request->orderId, $request->reason, $refundAmount);
        $this->shipping->cancelShipments($request->orderId);

        return Result::success(new OrderCancelledResponse(
            orderId: $request->orderId,
            refundAmount: $refundAmount,
            refundPercentage: $daysSinceOrder < 2 ? 100 : ($daysSinceOrder < 7 ? 50 : ($daysSinceOrder < 14 ? 25 : 0)),
        ));
    }
}

final readonly class CreateOrderRequest
{
    public function __construct(
        public ProductId $productId,
        public string $customerName,
        public int $quantity,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public string $taxType = 'general',
        public string $location = 'peninsula',
    ) {}
}

final readonly class ModifyOrderRequest
{
    public function __construct(
        public OrderId $orderId,
        public ?int $quantity = null,
        public ?string $customerName = null,
        public ?OrderStatus $status = null,
    ) {}
}

final readonly class CancelOrderRequest
{
    public function __construct(
        public OrderId $orderId,
        public string $reason,
    ) {}
}

final readonly class OrderCreatedResponse
{
    public function __construct(
        public OrderId $orderId,
        public PricingResult $pricing,
        public bool $isVip,
    ) {}
}

final readonly class OrderModifiedResponse
{
    public function __construct(
        public OrderId $orderId,
        public Money $newTotal,
        public Money $priceDifference,
    ) {}
}

final readonly class OrderCancelledResponse
{
    public function __construct(
        public OrderId $orderId,
        public Money $refundAmount,
        public int $refundPercentage,
    ) {}
}

final readonly class OrderDetail
{
    public function __construct(
        public OrderId $id,
        public string $customerName,
        public ?string $customerEmail,
        public int $quantity,
        public Money $unitPrice,
        public Money $subtotal,
        public Money $discount,
        public Money $tax,
        public Money $total,
        public OrderStatus $status,
        public bool $isVip,
        public string $createdAt,
        public array $shipments,
    ) {}
}

final readonly class OrderSummary
{
    public function __construct(
        public OrderId $id,
        public string $customerName,
        public Money $total,
        public OrderStatus $status,
        public string $createdAt,
    ) {}
}
