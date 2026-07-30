<?php

namespace App\Models;

use App\Enums\EmailThreadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailThread extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'contact_id',
        'campaign_id',
        'subject',
        'status',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => EmailThreadStatus::class,
            'last_message_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class)->orderBy('created_at')->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(EmailMessage::class)->latestOfMany();
    }

    public function hasBeenOpened(): bool
    {
        return $this->messages()
            ->where('direction', 'outbound')
            ->where(fn ($q) => $q->whereNotNull('opened_at')->orWhere('open_count', '>', 0))
            ->exists();
    }

    public function hasOutboundSent(): bool
    {
        return $this->messages()
            ->where('direction', 'outbound')
            ->where('delivery_status', 'sent')
            ->exists();
    }

    public function hasInboundReply(): bool
    {
        return $this->messages()->where('direction', 'inbound')->exists();
    }
}
