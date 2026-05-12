<?php

declare(strict_types=1);

namespace Tests\StateAndCoupling;

use AntiPatterns\Common\Database;
use AntiPatterns\StateAndCoupling\solution\BookingWorkflow;
use AntiPatterns\StateAndCoupling\solution\Coupon;
use AntiPatterns\StateAndCoupling\solution\PricingCalculator;
use AntiPatterns\StateAndCoupling\solution\Reservation;
use AntiPatterns\StateAndCoupling\solution\ReservationFactory;
use AntiPatterns\StateAndCoupling\solution\ReservationStatus;
use AntiPatterns\StateAndCoupling\solution\PaymentStatus;
use AntiPatterns\StateAndCoupling\solution\ShippingCalculator;
use AntiPatterns\StateAndCoupling\solution\ShippingConfig;
use AntiPatterns\StateAndCoupling\solution\ShippingMethod;
use AntiPatterns\StateAndCoupling\solution\StatusTransition;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the solution - demonstrates clean state management and explicit workflows.
 *
 * Key differences from antipattern tests:
 * 1. No mutable shared state - each operation takes input and returns output
 * 2. No temporal coupling - methods don't depend on call order
 * 3. No hidden side effects - read operations don't modify data
 * 4. Explicit state transitions via StatusTransition
 * 5. Environment configuration is injected, not detected
 * 6. Services are pure functions or explicit dependencies
 */
class BookingWorkflowTest extends TestCase
{
    private BookingWorkflow $workflow;
    private ReservationFactory $factory;

    protected function setUp(): void
    {
        Database::reset();
        $this->setUpDatabase();

        $db = Database::getInstance();
        $this->factory = new ReservationFactory($db);

        $shipping = new ShippingCalculator(new ShippingConfig());
        $pricing = new PricingCalculator(0.21);
        $transitions = new StatusTransition();

        $this->workflow = new BookingWorkflow($this->factory, $shipping, $pricing, $transitions, $db);
    }

    protected function tearDown(): void
    {
        Database::reset();
    }

    private function setUpDatabase(): void
    {
        $db = Database::getInstance();

        $db->exec("
            CREATE TABLE stores (id INTEGER PRIMARY KEY, name TEXT)
        ");

        $db->exec("
            CREATE TABLE customers (id INTEGER PRIMARY KEY, name TEXT, email TEXT)
        ");

        $db->exec("
            CREATE TABLE reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                customer_id INTEGER,
                base_price REAL,
                nights INTEGER,
                weight REAL DEFAULT 0,
                destination TEXT DEFAULT '',
                status INTEGER DEFAULT 1,
                payment_status INTEGER DEFAULT 0,
                transaction_id TEXT,
                coupon_code TEXT,
                discount REAL DEFAULT 0,
                total REAL DEFAULT 0,
                view_count INTEGER DEFAULT 0,
                notified INTEGER DEFAULT 0,
                created_at TEXT
            )
        ");

        $db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
        $db->exec("INSERT INTO customers (id, name, email) VALUES (1, 'Test Customer', 'test@example.com')");
        $db->exec("INSERT INTO reservations (id, store_id, customer_id, base_price, nights, weight, destination, status, payment_status, total, created_at) VALUES (1, 1, 1, 100.0, 3, 1.5, 'Madrid', 1, 0, 0, '2025-01-01')");
        $db->exec("INSERT INTO reservations (id, store_id, customer_id, base_price, nights, weight, destination, status, payment_status, total, created_at) VALUES (2, 1, 1, 200.0, 5, 12.0, 'Barcelona', 3, 0, 0, '2025-01-01')");
    }

    public function testConfirmReservationReturnsExplicitResult(): void
    {
        $result = $this->workflow->confirmReservation(1);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->data->reservation);
        $this->assertNotNull($result->data->pricing);
        $this->assertNotNull($result->data->shipping);
        $this->assertGreaterThan(0, $result->data->total);
    }

    public function testConfirmReservationFailsForNotFound(): void
    {
        $result = $this->workflow->confirmReservation(999);

        $this->assertFalse($result->success);
        $this->assertEquals('Reservation not found', $result->error);
    }

    public function testConfirmReservationFailsForInvalidTransition(): void
    {
        // Reservation 2 is already shipped (status 3), cannot confirm
        $result = $this->workflow->confirmReservation(2);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Cannot transition', $result->error);
    }

    public function testApplyCouponReturnsExplicitResult(): void
    {
        $result = $this->workflow->applyCoupon(1, 'SUMMER20');

        $this->assertTrue($result->success);
        $this->assertGreaterThan(0, $result->data->discount);
        $this->assertEquals('SUMMER20', $result->data->coupon->code);
    }

    public function testApplyCouponFailsForInvalidCode(): void
    {
        $result = $this->workflow->applyCoupon(1, 'INVALID');

        $this->assertFalse($result->success);
        $this->assertEquals('Invalid coupon code', $result->error);
    }

    public function testProcessPaymentReturnsExplicitResult(): void
    {
        $result = $this->workflow->processPayment(1, 'credit_card');

        $this->assertTrue($result->success);
        $this->assertStringStartsWith('txn-', $result->data->transactionId);
        $this->assertGreaterThan(0, $result->data->amount);
        $this->assertTrue($result->data->reservation->isPaid());
    }

    public function testProcessPaymentFailsForAlreadyPaid(): void
    {
        // First payment succeeds
        $this->workflow->processPayment(1, 'credit_card');

        // Second payment fails
        $result = $this->workflow->processPayment(1, 'credit_card');

        $this->assertFalse($result->success);
        $this->assertEquals('Reservation already paid', $result->error);
    }

    public function testNoSharedStateBetweenOperations(): void
    {
        $result1 = $this->workflow->confirmReservation(1);
        $this->assertTrue($result1->success);

        // Reservation 2 is already shipped, can't confirm - but that's explicit, not a state leak
        $result2 = $this->workflow->confirmReservation(2);
        $this->assertFalse($result2->success);
        $this->assertStringContainsString('Cannot transition', $result2->error);

        // Each operation is independent - no state leaks between them
        $this->assertNotNull($result1->data->total);
    }

    public function testNoHiddenSideEffectsOnRead(): void
    {
        $db = Database::getInstance();

        $viewCountBefore = $db->query("SELECT view_count FROM reservations WHERE id = 1")->fetchColumn();

        // Factory findById is a pure read - no side effects
        $reservation = $this->factory->findById(1);

        $viewCountAfter = $db->query("SELECT view_count FROM reservations WHERE id = 1")->fetchColumn();

        $this->assertNotNull($reservation);
        $this->assertEquals($viewCountBefore, $viewCountAfter); // No side effect!
    }

    public function testStatusTransitionIsValidated(): void
    {
        $transitions = new StatusTransition();

        // Valid transitions
        $this->assertTrue($transitions->canTransition(ReservationStatus::Pending, ReservationStatus::Confirmed));
        $this->assertTrue($transitions->canTransition(ReservationStatus::Pending, ReservationStatus::Cancelled));
        $this->assertTrue($transitions->canTransition(ReservationStatus::Confirmed, ReservationStatus::Shipped));

        // Invalid transitions
        $this->assertFalse($transitions->canTransition(ReservationStatus::Pending, ReservationStatus::Shipped));
        $this->assertFalse($transitions->canTransition(ReservationStatus::Completed, ReservationStatus::Pending));
        $this->assertFalse($transitions->canTransition(ReservationStatus::Cancelled, ReservationStatus::Confirmed));
    }

    public function testReservationIsImmutable(): void
    {
        $reservation = new Reservation(
            id: 1,
            customerId: 1,
            storeId: 1,
            basePrice: 100,
            nights: 3,
            weight: 1.5,
            destination: 'Madrid',
            status: ReservationStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid,
            transactionId: null,
            couponCode: null,
            createdAt: '2025-01-01',
        );

        $updated = $reservation->withStatus(ReservationStatus::Confirmed);

        // Original is unchanged
        $this->assertEquals(ReservationStatus::Pending, $reservation->status);
        // New instance has the change
        $this->assertEquals(ReservationStatus::Confirmed, $updated->status);
    }

    public function testShippingCalculatorIsPureFunction(): void
    {
        $calculator = new ShippingCalculator(new ShippingConfig());

        $reservation1 = new Reservation(
            id: 1, customerId: 1, storeId: 1, basePrice: 100, nights: 3,
            weight: 1.0, destination: 'Madrid', status: ReservationStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid, transactionId: null,
            couponCode: null, createdAt: '2025-01-01',
        );

        $reservation2 = new Reservation(
            id: 2, customerId: 1, storeId: 1, basePrice: 200, nights: 5,
            weight: 15.0, destination: 'Barcelona', status: ReservationStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid, transactionId: null,
            couponCode: null, createdAt: '2025-01-01',
        );

        $estimate1 = $calculator->calculate($reservation1);
        $estimate2 = $calculator->calculate($reservation2);

        // Different weights produce different shipping methods
        $this->assertEquals(ShippingMethod::Standard, $estimate1->method);
        $this->assertEquals(ShippingMethod::Freight, $estimate2->method);
        $this->assertGreaterThan($estimate1->cost, $estimate2->cost);
    }

    public function testShippingCalculatorRespectsAlreadyShipped(): void
    {
        $calculator = new ShippingCalculator(new ShippingConfig());

        $shipped = new Reservation(
            id: 1, customerId: 1, storeId: 1, basePrice: 100, nights: 3,
            weight: 1.0, destination: 'Madrid', status: ReservationStatus::Shipped,
            paymentStatus: PaymentStatus::Unpaid, transactionId: null,
            couponCode: null, createdAt: '2025-01-01',
        );

        $estimate = $calculator->calculate($shipped);

        $this->assertEquals(ShippingMethod::AlreadyShipped, $estimate->method);
        $this->assertEquals(0, $estimate->cost);
    }

    public function testCouponAppliesCorrectly(): void
    {
        $percentCoupon = Coupon::fromCode('SUMMER20');
        $this->assertNotNull($percentCoupon);
        $this->assertEquals(20, $percentCoupon->apply(100));

        $fixedCoupon = Coupon::fromCode('FLAT50');
        $this->assertNotNull($fixedCoupon);
        $this->assertEquals(50, $fixedCoupon->apply(100));

        // Fixed coupon capped at total
        $this->assertEquals(30, $fixedCoupon->apply(30));
    }

    public function testInvalidCouponReturnsNull(): void
    {
        $this->assertNull(Coupon::fromCode('INVALID'));
    }

    public function testPricingCalculatorIsPureFunction(): void
    {
        $calculator = new PricingCalculator(0.21);

        $reservation = new Reservation(
            id: 1, customerId: 1, storeId: 1, basePrice: 100, nights: 3,
            weight: 1.0, destination: 'Madrid', status: ReservationStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid, transactionId: null,
            couponCode: null, createdAt: '2025-01-01',
        );

        $pricing = $calculator->calculate($reservation);

        $this->assertEquals(300, $pricing->base);
        $this->assertEquals(63, $pricing->taxes);
        $this->assertEquals(363, $pricing->total);
    }

    public function testEachStepCanBeTestedIndependently(): void
    {
        // Pricing can be tested without database
        $pricing = new PricingCalculator(0.21);
        $reservation = new Reservation(
            id: 1, customerId: 1, storeId: 1, basePrice: 50, nights: 2,
            weight: 1.0, destination: 'Madrid', status: ReservationStatus::Pending,
            paymentStatus: PaymentStatus::Unpaid, transactionId: null,
            couponCode: null, createdAt: '2025-01-01',
        );
        $result = $pricing->calculate($reservation);
        $this->assertEquals(100, $result->base);

        // Shipping can be tested without database
        $shipping = new ShippingCalculator(new ShippingConfig());
        $estimate = $shipping->calculate($reservation);
        $this->assertGreaterThan(0, $estimate->cost);

        // Status transitions can be tested without any dependencies
        $transitions = new StatusTransition();
        $this->assertTrue($transitions->canTransition(ReservationStatus::Pending, ReservationStatus::Confirmed));
    }
}
