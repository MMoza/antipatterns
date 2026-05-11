<?php

declare(strict_types=1);

namespace Tests\GodClass;

use AntiPatterns\GodClass\solution\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Money value object.
 *
 * Replaces float-based money calculations that caused precision issues.
 */
class MoneyTest extends TestCase
{
    public function testCreatesFromFloat(): void
    {
        $money = new Money(19.99);
        $this->assertEquals(1999, $money->cents);
    }

    public function testCreatesFromCents(): void
    {
        $money = Money::fromCents(1999);
        $this->assertEquals(19.99, $money->amount());
    }

    public function testZero(): void
    {
        $money = Money::zero();
        $this->assertEquals(0, $money->cents);
    }

    public function testAddition(): void
    {
        $a = new Money(10.50);
        $b = new Money(5.25);
        $sum = $a->add($b);
        $this->assertEquals(1575, $sum->cents);
    }

    public function testSubtraction(): void
    {
        $a = new Money(10.50);
        $b = new Money(3.25);
        $diff = $a->subtract($b);
        $this->assertEquals(725, $diff->cents);
    }

    public function testMultiplication(): void
    {
        $money = new Money(33.33);
        $result = $money->multiply(3);
        $this->assertEquals(9999, $result->cents);
    }

    public function testPercentage(): void
    {
        $money = new Money(100);
        $result = $money->percentage(21);
        $this->assertEquals(2100, $result->cents);
    }

    public function testIsGreaterThan(): void
    {
        $a = new Money(100);
        $b = new Money(50);
        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    public function testFormat(): void
    {
        $money = new Money(1234.56);
        $this->assertEquals('€1,234.56', $money->format('€'));
    }

    public function testEquals(): void
    {
        $a = new Money(100);
        $b = new Money(100);
        $c = new Money(99.99);
        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testFloatPrecisionIssue(): void
    {
        $a = new Money(0.1);
        $b = new Money(0.2);
        $sum = $a->add($b);
        $this->assertEquals(30, $sum->cents);
    }
}
