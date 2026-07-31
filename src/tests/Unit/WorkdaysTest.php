<?php

namespace Tests\Unit;

use App\Support\Workdays;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Unit-тесты календаря рабочих дней (фаза 6.4).
 */
class WorkdaysTest extends TestCase
{
    /**
     * +2 рабочих дня с пятницы даёт вторник.
     *
     * @return void
     */
    public function test_add_two_workdays_skips_weekend(): void
    {
        // Пятница
        $from = Carbon::parse('2026-07-31 10:00:00');

        $result = Workdays::add($from, 2);

        // Пн 03.08 + Вт 04.08
        $this->assertSame('2026-08-04', $result->toDateString());
    }

    /**
     * Один календарный день вперёд — меньше 2 рабочих дней.
     *
     * @return void
     */
    public function test_one_calendar_day_is_not_two_workdays(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 12:00:00')); // вторник

        $this->assertFalse(
            Workdays::isAtLeastWorkdaysAhead(Carbon::parse('2026-07-29'), 2),
        );

        $this->assertTrue(
            Workdays::isAtLeastWorkdaysAhead(Carbon::parse('2026-07-30'), 2),
        );

        Carbon::setTestNow();
    }
}
