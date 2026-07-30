<?php

namespace App\Enums;

enum EmailThreadStatus: string
{
    case Open = 'open';
    case AwaitingReply = 'awaiting_reply';
    case Responded = 'responded';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::AwaitingReply => 'Awaiting reply',
            self::Responded => 'Responded',
            self::Closed => 'Closed',
        };
    }
}
