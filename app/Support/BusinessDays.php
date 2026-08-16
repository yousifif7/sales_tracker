<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class BusinessDays
{
    /**
     * Add N Mon–Fri business days after $from, then snap to the configured send hour
     * in the outreach timezone (defaults to Europe/London).
     */
    public static function addAfter(
        CarbonInterface $from,
        int $days,
        ?string $timezone = null,
        ?int $sendHour = null,
    ): Carbon {
        $timezone ??= (string) config('outreach.sequence.timezone', 'Europe/London');
        $sendHour ??= (int) config('outreach.sequence.send_hour', 9);

        $date = Carbon::parse($from)->timezone($timezone)->startOfDay();
        $added = 0;

        while ($added < $days) {
            $date->addDay();

            if ($date->isWeekday()) {
                $added++;
            }
        }

        return $date->setTime($sendHour, 0, 0);
    }

    public static function isBusinessDay(?CarbonInterface $at = null, ?string $timezone = null): bool
    {
        $timezone ??= (string) config('outreach.sequence.timezone', 'Europe/London');

        return Carbon::parse($at ?? now())->timezone($timezone)->isWeekday();
    }
}
