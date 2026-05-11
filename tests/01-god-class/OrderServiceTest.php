<?php

declare(strict_types=1);

namespace Tests\GodClass;

use AntiPatterns\Common\Database;
use AntiPatterns\GodClass\solution\Config;
use AntiPatterns\GodClass\solution\CreateOrderRequest;
use AntiPatterns\GodClass\solution\CancelOrderRequest;
use AntiPatterns\GodClass\solution\ModifyOrderRequest;
use AntiPatterns\GodClass\solution\OrderRepository;
use AntiPatterns\GodClass\solution\OrderService;
use AntiPatterns\GodClass\solution\OrderStatus;
use AntiPatterns\GodClass\solution\PricingService;
use AntiPatterns\GodClass\solution\ShippingService;
use AntiPatterns\GodClass\solution\ValueObjects\Money;
use AntiPatterns\GodClass\solution\ValueObjects\OrderId;
use AntiPatterns\GodClass\solution\ValueObjects\ProductId;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the solution - demonstrates clean architecture.
 *
 * Key differences from antipattern tests:
 * 1. Each service can be tested in isolation
 * 2. No mutable shared state between tests
 * 3. No hidden side effects
 * 4. Explicit dependencies via constructor
 * 5. Consistent Result return type
 * 6. No HTML mixed with business logic
 */
class OrderServiceTest extends TestCase
{
    private OrderService $service;
    private PricingService $pricing;
    private ShippingService $shipping;
    private OrderRepository $repository;

    protected function setUp(): void
    {
        Database::reset();
        $this->setUpDatabase();

        $db = Database::getInstance();
        $config = Config::default(storeId: 1);

        $this->repository = new OrderRepository($db, 1);
        $this->pricing = new PricingService($config);
        $this->shipping = new ShippingService($db, $config, 1);
        $this->service = new OrderService($this->repository, $this->pricing, $this->shipping);
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function setUpDatabase(): void
    {
        $db = Database::getInstance();

        $db->exec("
            CREATE TABLE IF NOT EXISTS stores (
                id INTEGER PRIMARY KEY,
                name TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY,
                store_id INTEGER,
                name TEXT,
                price REAL,
                stock INTEGER DEFAULT 0,
                reserved_stock INTEGER DEFAULT 0
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                product_id INTEGER,
                customer_name TEXT,
                customer_email TEXT,
                customer_phone TEXT,
                quantity INTEGER DEFAULT 1,
                unit_price REAL,
                subtotal REAL,
                discount REAL DEFAULT 0,
                tax REAL,
                total REAL,
                status INTEGER DEFAULT 1,
                is_vip INTEGER DEFAULT 0,
                cancellation_reason TEXT,
                refund_amount REAL DEFAULT 0,
                cancelled_at TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS shipments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                order_id INTEGER,
                shipping_type INTEGER DEFAULT 1,
                estimated_days INTEGER DEFAULT 5,
                shipping_cost REAL DEFAULT 0,
                status INTEGER DEFAULT 1,
                carrier TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT,
                phone TEXT,
                active INTEGER DEFAULT 1,
                merged_to INTEGER
            )
        ");

        $db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
        $db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (1, 1, 'Product A', 100.0, 50)");
        $db->exec("INSERT INTO products (id, store_id, name, price, stock) VALUES (2, 1, 'Product B', 150.0, 30)");
    }

    public function testCreateOrderReturnsCleanResult(): void
    {
        $request = new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'John Doe',
            customerEmail: 'john@example.com',
            customerPhone: '+34 600 123 456',
            quantity: 4,
        );

        $result = $this->service->createOrder($request);

        $this->assertTrue($result->isOk());
        $this->assertInstanceOf(\AntiPatterns\GodClass\solution\OrderCreatedResponse::class, $result->data);
        $this->assertGreaterThan(0, $result->data->orderId->value);
        $this->assertFalse($result->data->isVip);

        $pricing = $result->data->pricing;
        $this->assertEquals(400.0, $pricing->subtotal->amount());
        $this->assertEquals(20.0, $pricing->discount->amount());
        $this->assertEquals(79.8, round($pricing->tax->amount(), 2));
    }

    public function testCreateOrderWithBulkDiscount(): void
    {
        $request = new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'Bulk Buyer',
            quantity: 10,
        );

        $result = $this->service->createOrder($request);

        $this->assertTrue($result->isOk());
        $pricing = $result->data->pricing;
        $this->assertEquals(1000.0, $pricing->subtotal->amount());
        $this->assertEquals(100.0, $pricing->discount->amount());
        $this->assertFalse($result->data->isVip);
    }

    public function testCreateOrderFailsWithInsufficientStock(): void
    {
        $request = new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'No Stock',
            quantity: 999,
        );

        $result = $this->service->createOrder($request);

        $this->assertTrue($result->isFail());
        $this->assertEquals('Insufficient stock', $result->error);
    }

    public function testGetOrderReturnsDetail(): void
    {
        $createResult = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'Test Customer',
            quantity: 2,
        ));

        $orderId = $createResult->data->orderId;
        $result = $this->service->getOrder($orderId);

        $this->assertTrue($result->isOk());
        $detail = $result->data;
        $this->assertEquals('Test Customer', $detail->customerName);
        $this->assertEquals(2, $detail->quantity);
        $this->assertEquals(OrderStatus::Pending, $detail->status);
        $this->assertNotEmpty($detail->shipments);
    }

    public function testGetOrderReturnsFailureForNonExistent(): void
    {
        $result = $this->service->getOrder(new OrderId(99999));

        $this->assertTrue($result->isFail());
        $this->assertEquals('Order not found', $result->error);
    }

    public function testListOrdersReturnsAllOrders(): void
    {
        $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'Customer 1',
            quantity: 1,
        ));

        $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(2),
            customerName: 'Customer 2',
            quantity: 2,
        ));

        $result = $this->service->listOrders();

        $this->assertTrue($result->isOk());
        $this->assertCount(2, $result->data);
    }

    public function testModifyOrderRecalculatesPricing(): void
    {
        $createResult = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'Modify Test',
            quantity: 1,
        ));

        $orderId = $createResult->data->orderId;

        $result = $this->service->modifyOrder(new ModifyOrderRequest(
            orderId: $orderId,
            quantity: 5,
        ));

        $this->assertTrue($result->isOk());
        $this->assertGreaterThan(0, $result->data->newTotal->amount());
    }

    public function testCancelOrderCalculatesCorrectRefund(): void
    {
        $createResult = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'Cancel Test',
            quantity: 2,
        ));

        $orderId = $createResult->data->orderId;

        $result = $this->service->cancelOrder(new CancelOrderRequest(
            orderId: $orderId,
            reason: 'Changed mind',
        ));

        $this->assertTrue($result->isOk());
        $this->assertEquals(100, $result->data->refundPercentage);
        $this->assertTrue($result->data->refundAmount->cents > 0);
    }

    public function testNoMutableSharedStateBetweenOperations(): void
    {
        $result1 = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'First',
            quantity: 1,
        ));

        $result2 = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(2),
            customerName: 'Second',
            quantity: 2,
        ));

        $this->assertNotEquals($result1->data->orderId->value, $result2->data->orderId->value);
        $this->assertEquals('First', $this->service->getOrder($result1->data->orderId)->data->customerName);
        $this->assertEquals('Second', $this->service->getOrder($result2->data->orderId)->data->customerName);
    }

    public function testNoHtmlInBusinessLogicResults(): void
    {
        $result = $this->service->createOrder(new CreateOrderRequest(
            productId: new ProductId(1),
            customerName: 'API User',
            quantity: 1,
        ));

        $this->assertNotInstanceOf(\stdClass::class, $result->data);
        $this->assertObjectNotHasProperty('html', $result->data);
    }

    public function testServicesAreIsolatable(): void
    {
        $pricing = new PricingService(Config::default());

        $result = $pricing->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 5,
        );

        $this->assertEquals(500.0, $result->subtotal->amount());
        $this->assertEquals(25.0, $result->discount->amount());
    }

    public function testConfigIsExternalized(): void
    {
        $config = new Config(
            storeId: 1,
            taxes: new \AntiPatterns\GodClass\solution\TaxConfig(general: 0.25),
            discounts: new \AntiPatterns\GodClass\solution\DiscountConfig(bulkThreshold: 10, bulkPercentage: 0.20),
            shipping: new \AntiPatterns\GodClass\solution\ShippingConfig(),
        );

        $pricing = new PricingService($config);
        $result = $pricing->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 10,
        );

        $this->assertEquals(200.0, $result->discount->amount());
    }
}
