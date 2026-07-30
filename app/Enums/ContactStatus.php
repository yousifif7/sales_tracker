<?php

namespace App\Enums;

enum ContactStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Responded = 'responded';
    case Qualified = 'qualified';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Responded => 'Responded',
            self::Qualified => 'Qualified',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
