<?php

declare(strict_types=1);

namespace Tests\StateAndCoupling;

use AntiPatterns\Common\Database;
use AntiPatterns\StateAndCoupling\antipattern\ReservationContext;
use PHPUnit\Framework\TestCase;

/**
 * Tests that demonstrate the problems with mutable shared state and coupling.
 *
 * Each test shows a specific antipattern from section B (9-16).
 */
class ReservationContextTest extends TestCase
{
    private ReservationContext $context;

    protected function setUp(): void
    {
        Database::reset();
        $this->setUpDatabase();
        $this->context = new ReservationContext(['store_id' => 1]);
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
            CREATE TABLE customers (
                id INTEGER PRIMARY KEY,
                name TEXT,
                email TEXT,
                last_activity TEXT
            )
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

        $db->exec("
            CREATE TABLE availability (
                date TEXT PRIMARY KEY,
                available INTEGER,
                view_count INTEGER DEFAULT 0
            )
        ");

        $db->exec("
            CREATE TABLE availability_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_id INTEGER,
                alert_type TEXT,
                created_at TEXT
            )
        ");

        $db->exec("
            CREATE TABLE tax_rates (store_id INTEGER PRIMARY KEY, rate REAL)
        ");
        $db->exec("
            CREATE TABLE discount_rules (store_id INTEGER PRIMARY KEY, rule TEXT)
        ");
        $db->exec("
            CREATE TABLE carriers (store_id INTEGER PRIMARY KEY, name TEXT)
        ");
        $db->exec("
            CREATE TABLE shipping_zones (store_id INTEGER PRIMARY KEY, zone TEXT)
        ");
        $db->exec("
            CREATE TABLE email_templates (store_id INTEGER PRIMARY KEY, template TEXT)
        ");

        $db->exec("INSERT INTO stores (id, name) VALUES (1, 'Test Store')");
        $db->exec("INSERT INTO customers (id, name, email) VALUES (1, 'Test Customer', 'test@example.com')");
        $db->exec("INSERT INTO reservations (id, store_id, customer_id, base_price, nights, weight, destination, status, payment_status, total, created_at) VALUES (1, 1, 1, 100.0, 3, 1.5, 'Madrid', 1, 0, 0, '2025-01-01')");
        $db->exec("INSERT INTO reservations (id, store_id, customer_id, base_price, nights, weight, destination, status, payment_status, total, created_at) VALUES (2, 1, 1, 200.0, 5, 12.0, 'Barcelona', 3, 0, 0, '2025-01-01')");
        $db->exec("INSERT INTO availability (date, available) VALUES ('2025-06-01', 2)");
        $db->exec("INSERT INTO availability (date, available) VALUES ('2025-06-02', 1)");
    }

    /**
     * #9 Mutable Shared State - context is modified by every method
     * and can be read/changed from anywhere.
     */
    public function testMutableSharedStateIsExposed(): void
    {
        $this->context->loadReservation(1);

        $context = $this->context->getContext();
        $this->assertEquals(1, $context['reservation_id']);
        $this->assertEquals(1, $context['status']);

        // Context exposes internal state - anyone can read sensitive data
        $this->assertArrayHasKey('store_id', $context);
        $this->assertArrayHasKey('currency', $context);
    }

    /**
     * #11 Temporal Coupling - methods must be called in specific order.
     * calculatePrices() fails if loadReservation() wasn't called first.
     */
    public function testTemporalCouplingBetweenMethods(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Must call loadReservation() first');

        // Calling calculatePrices() without loadReservation() first
        $this->context->calculatePrices();
    }

    /**
     * #11 Temporal Coupling - the full workflow requires specific order.
     */
    public function testFullWorkflowRequiresSpecificOrder(): void
    {
        $this->context->loadReservation(1);
        $this->context->calculatePrices();

        // calculateShipping() depends on context['total'] set by calculatePrices()
        $shipping = $this->context->calculateShipping();
        // In dev environment, shipping is free for total > 100
        $this->assertGreaterThanOrEqual(0, $shipping['cost']);

        // applyDiscounts() depends on context['prices_calculated'] set by calculatePrices()
        $discount = $this->context->applyDiscounts('SUMMER20');
        $this->assertGreaterThan(0, $discount['discount']);

        // processPayment() depends on prices being calculated
        $payment = $this->context->processPayment('credit_card');
        $this->assertTrue($payment['success']);
    }

    /**
     * #10 Action at Distance - loadReservation() sets shipping_locked
     * which silently changes calculateShipping() behavior.
     */
    public function testActionAtDistanceFromLoadReservation(): void
    {
        // Reservation 2 has status >= 3, so loadReservation sets shipping_locked
        $this->context->loadReservation(2);

        // calculateShipping() returns 0 cost because of the flag set in loadReservation()
        $shipping = $this->context->calculateShipping();
        $this->assertEquals(0, $shipping['cost']);
        $this->assertEquals('already_shipped', $shipping['method']);
    }

    /**
     * #10 Action at Distance - calculatePrices() sets context['total']
     * which is used by calculateShipping() for cost calculation.
     */
    public function testActionAtDistanceFromCalculatePrices(): void
    {
        $this->context->loadReservation(1);

        // Without calculatePrices(), shipping uses default total of 0
        $shippingBefore = $this->context->calculateShipping();
        $this->assertEquals(9.99, $shippingBefore['cost']); // default for total < 100

        $this->context->loadReservation(1); // Reset
        $this->context->calculatePrices(); // Sets context['total'] to 396.9

        // Now shipping uses the calculated total
        $shippingAfter = $this->context->calculateShipping();
        $this->assertEquals(0, $shippingAfter['cost']); // free shipping for total > 100
    }

    /**
     * #14 Hidden Side Effects - getAvailableDates() modifies database
     * (increments view_count, creates alerts) while appearing to be a read-only query.
     */
    public function testHiddenSideEffectsInQueryMethod(): void
    {
        $db = Database::getInstance();

        // Before: no alerts, view_count = 0
        $alertsBefore = $db->query("SELECT COUNT(*) FROM availability_alerts")->fetchColumn();
        $this->assertEquals(0, $alertsBefore);

        // Call the "getter" method
        $this->context->getAvailableDates('2025-06-01', '2025-06-02');

        // After: alerts were created and view_count was incremented
        $alertsAfter = $db->query("SELECT COUNT(*) FROM availability_alerts")->fetchColumn();
        $this->assertGreaterThan($alertsBefore, $alertsAfter);

        $viewCount = $db->query("SELECT SUM(view_count) FROM availability")->fetchColumn();
        $this->assertGreaterThan(0, $viewCount);
    }

    /**
     * #14 Hidden Side Effects - getReservationSummary() modifies database
     * (increments view_count, updates last_activity) while appearing read-only.
     */
    public function testHiddenSideEffectsInSummaryMethod(): void
    {
        $db = Database::getInstance();

        $this->context->loadReservation(1);

        $viewCountBefore = $db->query("SELECT view_count FROM reservations WHERE id = 1")->fetchColumn();
        $this->assertEquals(0, $viewCountBefore);

        // Call the "getter" method
        $this->context->getReservationSummary();

        // Side effect: view_count was incremented
        $viewCountAfter = $db->query("SELECT view_count FROM reservations WHERE id = 1")->fetchColumn();
        $this->assertEquals(1, $viewCountAfter);
    }

    /**
     * #15 Recursive Service Instantiation - each service creates its own
     * dependencies, creating deep chains that are hard to trace.
     */
    public function testRecursiveServiceInstantiation(): void
    {
        $this->context->loadReservation(1);

        // When calculatePrices() is called, it triggers:
        // ReservationContext -> PricingService -> TaxService -> TaxRateLoader -> DB
        // ReservationContext -> PricingService -> DiscountService -> DB
        // All hidden inside the method call
        $prices = $this->context->calculatePrices();

        $this->assertArrayHasKey('base', $prices);
        $this->assertArrayHasKey('taxes', $prices);
        $this->assertArrayHasKey('total', $prices);
    }

    /**
     * #16 Environment Logic Scattered - behavior changes based on
     * detected environment, with logic spread across multiple methods.
     */
    public function testEnvironmentLogicAffectsBehavior(): void
    {
        $flags = $this->context->getFlags();

        // In development (default), payments are mocked and emails are not skipped
        $this->assertTrue($flags['use_mock_payments']);
        $this->assertFalse($flags['skip_email']);
        $this->assertTrue($flags['enable_debug']);
    }

    /**
     * Demonstrates that state from one operation leaks into another.
     * Two separate reservations interfere with each other.
     */
    public function testStateLeaksBetweenOperations(): void
    {
        $this->context->loadReservation(1);
        $this->assertEquals(1, $this->context->getContext()['reservation_id']);

        $this->context->loadReservation(2);
        $this->assertEquals(2, $this->context->getContext()['reservation_id']);

        // The context state was overwritten - previous operation's context is lost
        // If another part of the code was still using the old context values,
        // they would now be working with the wrong reservation
        $this->assertEquals(2, $this->context->getContext()['reservation_id']);
        $this->assertEquals(3, $this->context->getContext()['status']);
    }

    /**
     * Demonstrates that you cannot test a single operation in isolation.
     * Every operation requires the full context to be set up.
     */
    public function testCannotIsolateSingleOperation(): void
    {
        // To test applyDiscounts() alone, you need to:
        // 1. Set up database
        // 2. Create a reservation
        // 3. Call loadReservation()
        // 4. Call calculatePrices()
        // 5. THEN call applyDiscounts()

        $this->context->loadReservation(1);
        $this->context->calculatePrices();

        $result = $this->context->applyDiscounts('SUMMER20');
        $this->assertArrayHasKey('discount', $result);
        $this->assertGreaterThan(0, $result['discount']);
    }
}
