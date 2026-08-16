<?php

namespace App\Enums;

enum EmailSequenceNextStep: string
{
    case Followup = 'followup';
    case Nudge = 'nudge';
    case Exit = 'exit';

    public function label(): string
    {
        return match ($this) {
            self::Followup => 'Follow-up',
            self::Nudge => 'Final nudge',
            self::Exit => 'Exit check',
        };
    }
}
