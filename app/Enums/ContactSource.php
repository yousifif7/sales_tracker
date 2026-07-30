<?php

namespace App\Enums;

enum ContactSource: string
{
    case Manual = 'manual';
    case AiSearch = 'ai_search';
    case Referral = 'referral';
    case Import = 'import';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::AiSearch => 'AI Search',
            self::Referral => 'Referral',
            self::Import => 'Import',
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
