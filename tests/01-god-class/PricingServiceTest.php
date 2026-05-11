<?php

declare(strict_types=1);

namespace Tests\GodClass;

use AntiPatterns\GodClass\solution\Config;
use AntiPatterns\GodClass\solution\Coupon;
use AntiPatterns\GodClass\solution\PricingService;
use AntiPatterns\GodClass\solution\TaxConfig;
use AntiPatterns\GodClass\solution\DiscountConfig;
use AntiPatterns\GodClass\solution\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PricingService - demonstrates isolated testability.
 *
 * In the antipattern, pricing logic was buried in createOrder(),
 * modifyOrder(), and generateInvoice() with duplicated code.
 * Now it's a single service testable without database or other dependencies.
 */
class PricingServiceTest extends TestCase
{
    private PricingService $service;

    protected function setUp(): void
    {
        $this->service = new PricingService(Config::default());
    }

    public function testNoDiscountForSmallQuantity(): void
    {
        $result = $this->service->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 1,
        );

        $this->assertEquals(100.0, $result->subtotal->amount());
        $this->assertEquals(0, $result->discount->cents);
        $this->assertEquals(21.0, $result->tax->amount());
        $this->assertEquals(121.0, $result->total->amount());
    }

    public function testMediumDiscountForThreeOrMore(): void
    {
        $result = $this->service->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 3,
        );

        $this->assertEquals(300.0, $result->subtotal->amount());
        $this->assertEquals(15.0, $result->discount->amount());
        $this->assertEquals(344.85, round($result->total->amount(), 2));
    }

    public function testBulkDiscountForSevenOrMore(): void
    {
        $result = $this->service->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 7,
        );

        $this->assertEquals(700.0, $result->subtotal->amount());
        $this->assertEquals(70.0, $result->discount->amount());
    }

    public function testTaxCalculationGeneral(): void
    {
        $tax = $this->service->calculateTax(new Money(1000), 'general', 'peninsula');
        $this->assertEquals(210.0, $tax->amount());
    }

    public function testTaxCalculationCanarias(): void
    {
        $tax = $this->service->calculateTax(new Money(1000), 'general', 'canarias');
        $this->assertEquals(70.0, $tax->amount());
    }

    public function testTaxCalculationCeutaMelilla(): void
    {
        $tax = $this->service->calculateTax(new Money(1000), 'general', 'ceuta_melilla');
        $this->assertEquals(40.0, $tax->amount());
    }

    public function testTaxCalculationReduced(): void
    {
        $tax = $this->service->calculateTax(new Money(1000), 'reduced');
        $this->assertEquals(100.0, $tax->amount());
    }

    public function testPercentageCoupon(): void
    {
        $coupon = new Coupon('SUMMER10', 'percentage', 10);
        $result = $this->service->applyCoupon(new Money(500), $coupon);
        $this->assertEquals(450.0, $result->amount());
    }

    public function testFixedCoupon(): void
    {
        $coupon = new Coupon('FLAT50', 'fixed', 50);
        $result = $this->service->applyCoupon(new Money(500), $coupon);
        $this->assertEquals(450.0, $result->amount());
    }

    public function testRefundFullWithinTwoDays(): void
    {
        $refund = $this->service->calculateRefund(new Money(200), 1);
        $this->assertEquals(200.0, $refund->amount());
    }

    public function testRefundHalfWithinSevenDays(): void
    {
        $refund = $this->service->calculateRefund(new Money(200), 5);
        $this->assertEquals(100.0, $refund->amount());
    }

    public function testRefundQuarterWithinFourteenDays(): void
    {
        $refund = $this->service->calculateRefund(new Money(200), 10);
        $this->assertEquals(50.0, $refund->amount());
    }

    public function testNoRefundAfterFourteenDays(): void
    {
        $refund = $this->service->calculateRefund(new Money(200), 20);
        $this->assertEquals(0, $refund->cents);
    }

    public function testCustomConfigChangesDiscounts(): void
    {
        $config = new Config(
            storeId: 1,
            taxes: TaxConfig::default(),
            discounts: new DiscountConfig(
                bulkThreshold: 5,
                bulkPercentage: 0.15,
                mediumThreshold: 2,
                mediumPercentage: 0.08,
            ),
            shipping: new \AntiPatterns\GodClass\solution\ShippingConfig(),
        );

        $service = new PricingService($config);
        $result = $service->calculateOrderTotal(
            unitPrice: new Money(100),
            quantity: 5,
        );

        $this->assertEquals(75.0, $result->discount->amount());
    }

    public function testMoneyPrecisionIsCorrect(): void
    {
        $price = new Money(33.33);
        $result = $this->service->calculateOrderTotal(
            unitPrice: $price,
            quantity: 3,
        );

        $this->assertEquals(99.99, $result->subtotal->amount());
    }

    public function testMoneyAdditionIsAccurate(): void
    {
        $a = new Money(0.1);
        $b = new Money(0.2);
        $sum = $a->add($b);
        $this->assertEquals(30, $sum->cents);
    }
}
