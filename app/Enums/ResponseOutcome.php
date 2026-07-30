<?php

namespace App\Enums;

enum ResponseOutcome: string
{
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case NoResponse = 'no_response';
    case NeedsFollowup = 'needs_followup';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Interested',
            self::NotInterested => 'Not Interested',
            self::NoResponse => 'No Response',
            self::NeedsFollowup => 'Needs Follow-up',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
