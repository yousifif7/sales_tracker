<?php

namespace Tests\Unit;

use App\Support\BusinessDays;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BusinessDaysTest extends TestCase
{
    public function test_adds_business_days_skipping_weekends(): void
    {
        // Thursday 14 Aug 2025 → +4 BD = Wednesday 20 Aug 2025
        $from = Carbon::parse('2025-08-14 15:30:00', 'Europe/London');

        $result = BusinessDays::addAfter($from, 4, 'Europe/London', 9);

        $this->assertSame('2025-08-20', $result->toDateString());
        $this->assertSame(9, $result->hour);
        $this->assertTrue($result->isWednesday());
    }

    public function test_thursday_plus_one_business_day_is_friday(): void
    {
        $from = Carbon::parse('2025-08-14 10:00:00', 'Europe/London');

        $result = BusinessDays::addAfter($from, 1, 'Europe/London', 9);

        $this->assertSame('2025-08-15', $result->toDateString());
        $this->assertTrue($result->isFriday());
    }

    public function test_friday_plus_one_business_day_is_monday(): void
    {
        $from = Carbon::parse('2025-08-15 10:00:00', 'Europe/London');

        $result = BusinessDays::addAfter($from, 1, 'Europe/London', 9);

        $this->assertSame('2025-08-18', $result->toDateString());
        $this->assertTrue($result->isMonday());
    }

    public function test_is_business_day(): void
    {
        $this->assertTrue(BusinessDays::isBusinessDay(Carbon::parse('2025-08-15', 'Europe/London'), 'Europe/London'));
        $this->assertFalse(BusinessDays::isBusinessDay(Carbon::parse('2025-08-16', 'Europe/London'), 'Europe/London'));
        $this->assertFalse(BusinessDays::isBusinessDay(Carbon::parse('2025-08-17', 'Europe/London'), 'Europe/London'));
    }
}
