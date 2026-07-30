<?php

namespace App\Enums;

enum CampaignChannel: string
{
    case Email = 'email';
    case Call = 'call';
    case Linkedin = 'linkedin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Call => 'Call',
            self::Linkedin => 'LinkedIn',
            self::Other => 'Other',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
