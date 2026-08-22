<?php

namespace App\Models;

use App\Enums\EmailSequenceExitReason;
use App\Enums\EmailSequenceNextStep;
use App\Enums\EmailSequenceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailSequenceEnrollment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'contact_id',
        'email_thread_id',
        'cold_message_id',
        'campaign_id',
        'created_by',
        'status',
        'next_step',
        'next_action_at',
        'enrolled_at',
        'followup_sent_at',
        'nudge_sent_at',
        'completed_at',
        'exit_reason',
        'cold_subject',
        'followup_template_slug',
        'nudge_template_slug',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmailSequenceStatus::class,
            'next_step' => EmailSequenceNextStep::class,
            'exit_reason' => EmailSequenceExitReason::class,
            'next_action_at' => 'datetime',
            'enrolled_at' => 'datetime',
            'followup_sent_at' => 'datetime',
            'nudge_sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(EmailThread::class, 'email_thread_id');
    }

    public function coldMessage(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'cold_message_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', EmailSequenceStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === EmailSequenceStatus::Active;
    }

    public function canMarkCurrentStepComplete(): bool
    {
        return $this->isActive() && in_array($this->next_step, [
            EmailSequenceNextStep::Followup,
            EmailSequenceNextStep::Nudge,
        ], true);
    }

    public function canSendNow(): bool
    {
        return $this->isActive() && in_array($this->next_step, [
            EmailSequenceNextStep::Followup,
            EmailSequenceNextStep::Nudge,
            EmailSequenceNextStep::Exit,
        ], true);
    }

    public function canReactivate(): bool
    {
        if ($this->status !== EmailSequenceStatus::Completed) {
            return false;
        }

        return in_array($this->exit_reason, [
            EmailSequenceExitReason::MissingTemplate,
            EmailSequenceExitReason::SendFailed,
        ], true);
    }

    public function markStepCompleteLabel(): ?string
    {
        return match ($this->next_step) {
            EmailSequenceNextStep::Followup => 'Mark follow-up sent',
            EmailSequenceNextStep::Nudge => 'Mark nudge sent',
            default => null,
        };
    }

    public function sendNowLabel(): string
    {
        return match ($this->next_step) {
            EmailSequenceNextStep::Followup => 'Send follow-up now',
            EmailSequenceNextStep::Nudge => 'Send nudge now',
            EmailSequenceNextStep::Exit => 'Run exit check now',
            default => 'Send now',
        };
    }
}
