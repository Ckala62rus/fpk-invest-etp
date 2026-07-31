<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Календарь рабочих дней (пн–пт) для сроков уточнения КП.
 *
 * Праздники РФ пока не учитываются — достаточно правила «мин. 2 рабочих дня» из ТЗ.
 */
final class Workdays
{
    /**
     * Добавляет N рабочих дней к дате (выходные пропускаются).
     *
     * @param Carbon $from Исходная дата/время
     * @param int $days Сколько рабочих дней добавить (строго > 0)
     * @return Carbon Новая дата (копия), время 23:59:59 для удобства дедлайнов
     */
    public static function add(Carbon $from, int $days): Carbon
    {
        $date = $from->copy()->startOfDay();
        $added = 0;

        while ($added < $days) {
            $date->addDay();
            if ($date->isWeekday()) {
                $added++;
            }
        }

        return $date->endOfDay();
    }

    /**
     * Проверяет, что дедлайн не раньше чем через $minWorkdays рабочих дней.
     *
     * @param Carbon $deadline Предлагаемый срок
     * @param int $minWorkdays Минимум рабочих дней (по ТЗ — 2)
     * @param Carbon|null $from От какой даты считать (по умолчанию сейчас)
     * @return bool
     */
    public static function isAtLeastWorkdaysAhead(
        Carbon $deadline,
        int $minWorkdays = 2,
        ?Carbon $from = null,
    ): bool {
        $from ??= now();
        $minAllowed = self::add($from, $minWorkdays);

        return $deadline->greaterThanOrEqualTo($minAllowed->copy()->startOfDay());
    }
}
