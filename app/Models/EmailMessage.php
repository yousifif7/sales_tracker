<?php

namespace App\Models;

use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMessage extends Model
{
    protected $fillable = [
        'email_thread_id',
        'interaction_id',
        'created_by',
        'direction',
        'from_email',
        'to_email',
        'subject',
        'body_html',
        'body_text',
        'message_id',
        'in_reply_to',
        'references',
        'imap_uid',
        'imap_folder',
        'tracking_token',
        'delivery_status',
        'sent_at',
        'received_at',
        'opened_at',
        'open_count',
    ];

    protected function casts(): array
    {
        return [
            'direction' => EmailMessageDirection::class,
            'delivery_status' => EmailDeliveryStatus::class,
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
            'opened_at' => 'datetime',
            'open_count' => 'integer',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class, 'email_thread_id');
    }

    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recordOpen(): void
    {
        $this->forceFill([
            'opened_at' => $this->opened_at ?? now(),
            'open_count' => $this->open_count + 1,
        ])->save();
    }

    public function previewText(int $length = 100): string
    {
        $text = $this->body_text;

        if (! filled($text) && filled($this->body_html)) {
            $text = html_entity_decode(strip_tags((string) $this->body_html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $text = preg_replace('/\s+/u', ' ', trim((string) $text)) ?: '';

        return \Illuminate\Support\Str::limit($text, $length);
    }

    public function normalizedMessageId(): ?string
    {
        return self::normalizeMessageId($this->message_id);
    }

    public static function normalizeMessageId(?string $messageId): ?string
    {
        if (! filled($messageId)) {
            return null;
        }

        return strtolower(trim($messageId, " \t\n\r\0\x0B<>"));
    }
}
