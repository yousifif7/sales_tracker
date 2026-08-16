<?php

namespace App\Enums;

enum EmailSequenceExitReason: string
{
    case Replied = 'replied';
    case StatusChanged = 'status_changed';
    case Cancelled = 'cancelled';
    case QuietLost = 'quiet_lost';
    case HotOpens = 'hot_opens';
    case MissingTemplate = 'missing_template';
    case SendFailed = 'send_failed';

    public function label(): string
    {
        return match ($this) {
            self::Replied => 'Replied',
            self::StatusChanged => 'Status changed',
            self::Cancelled => 'Cancelled',
            self::QuietLost => 'No reply — marked lost',
            self::HotOpens => 'Hot opens — needs LinkedIn',
            self::MissingTemplate => 'Missing template',
            self::SendFailed => 'Send failed',
        };
    }
}
