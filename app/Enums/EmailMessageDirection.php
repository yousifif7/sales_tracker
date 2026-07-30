<?php

namespace App\Enums;

enum EmailMessageDirection: string
{
    case Outbound = 'outbound';
    case Inbound = 'inbound';

    public function label(): string
    {
        return match ($this) {
            self::Outbound => 'Sent',
            self::Inbound => 'Received',
        };
    }
}
