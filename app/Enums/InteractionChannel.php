<?php

namespace App\Enums;

enum InteractionChannel: string
{
    case Email = 'email';
    case Call = 'call';
    case Linkedin = 'linkedin';
    case Whatsapp = 'whatsapp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Call => 'Call',
            self::Linkedin => 'LinkedIn',
            self::Whatsapp => 'WhatsApp',
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
