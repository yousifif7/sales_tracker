<?php

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use App\Enums\EmailSequenceExitReason;
use App\Enums\EmailSequenceNextStep;
use App\Enums\EmailSequenceStatus;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailThread;
use App\Models\FollowUp;
use App\Support\BusinessDays;
use App\Support\OutreachTemplateRenderer;
use Illuminate\Support\Facades\Log;
use Throwable;

class OutreachSequenceService
{
    public function __construct(
        protected OutreachEmailService $outreachEmailService,
        protected OutreachTemplateRenderer $templates,
    ) {
    }

    public function enroll(
        Contact $contact,
        EmailThread $thread,
        EmailMessage $coldMessage,
        ?int $campaignId = null,
        ?int $userId = null,
    ): ?EmailSequenceEnrollment {
        if ($this->activeEnrollmentFor($contact)) {
            return null;
        }

        $enrolledAt = $coldMessage->sent_at ?? now();
        $followupDays = (int) config('outreach.sequence.followup_business_days', 4);

        return EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $coldMessage->id,
            'campaign_id' => $campaignId ?? $thread->campaign_id,
            'created_by' => $userId,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Followup,
            'next_action_at' => BusinessDays::addAfter($enrolledAt, $followupDays),
            'enrolled_at' => $enrolledAt,
            'cold_subject' => $this->outreachEmailService->stripSubjectPrefixes($coldMessage->subject),
            'followup_template_slug' => (string) config('outreach.sequence.followup_template', 'fieldline_followup'),
            'nudge_template_slug' => (string) config('outreach.sequence.nudge_template', 'fieldline_final_nudge'),
        ]);
    }

    public function activeEnrollmentFor(Contact $contact): ?EmailSequenceEnrollment
    {
        return EmailSequenceEnrollment::query()
            ->active()
            ->where('contact_id', $contact->id)
            ->latest('id')
            ->first();
    }

    public function completeForContact(Contact $contact, EmailSequenceExitReason $reason): void
    {
        EmailSequenceEnrollment::query()
            ->active()
            ->where('contact_id', $contact->id)
            ->get()
            ->each(fn (EmailSequenceEnrollment $enrollment) => $this->complete($enrollment, $reason));
    }

    public function complete(EmailSequenceEnrollment $enrollment, EmailSequenceExitReason $reason): void
    {
        if (! $enrollment->isActive()) {
            return;
        }

        $enrollment->update([
            'status' => EmailSequenceStatus::Completed,
            'completed_at' => now(),
            'exit_reason' => $reason,
            'next_action_at' => null,
        ]);
    }

    /**
     * @return array{processed: int, sent: int, exited: int, skipped: int, errors: int}
     */
    public function processDue(): array
    {
        $stats = [
            'processed' => 0,
            'sent' => 0,
            'exited' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        if (! BusinessDays::isBusinessDay()) {
            return $stats;
        }

        $due = EmailSequenceEnrollment::query()
            ->active()
            ->whereNotNull('next_action_at')
            ->where('next_action_at', '<=', now())
            ->with(['contact', 'thread', 'coldMessage'])
            ->orderBy('next_action_at')
            ->limit(50)
            ->get();

        foreach ($due as $enrollment) {
            $stats['processed']++;

            try {
                $result = $this->processEnrollment($enrollment);

                match ($result) {
                    'sent' => $stats['sent']++,
                    'exited' => $stats['exited']++,
                    default => $stats['skipped']++,
                };
            } catch (Throwable $exception) {
                report($exception);
                $stats['errors']++;
                Log::warning('Sequence processing failed', [
                    'enrollment_id' => $enrollment->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $stats;
    }

    protected function processEnrollment(EmailSequenceEnrollment $enrollment): string
    {
        $contact = $enrollment->contact;

        if (! $contact || ! filled($contact->email)) {
            $this->complete($enrollment, EmailSequenceExitReason::Cancelled);

            return 'exited';
        }

        if ($this->shouldStopForStatus($contact->status)) {
            $this->complete($enrollment, $contact->status === ContactStatus::Responded
                ? EmailSequenceExitReason::Replied
                : EmailSequenceExitReason::StatusChanged);

            return 'exited';
        }

        if ($this->contactHasInboundReply($contact)) {
            $this->complete($enrollment, EmailSequenceExitReason::Replied);

            return 'exited';
        }

        return match ($enrollment->next_step) {
            EmailSequenceNextStep::Followup => $this->sendStep($enrollment, EmailSequenceNextStep::Followup),
            EmailSequenceNextStep::Nudge => $this->sendStep($enrollment, EmailSequenceNextStep::Nudge),
            EmailSequenceNextStep::Exit => $this->exitSequence($enrollment),
        };
    }

    protected function sendStep(EmailSequenceEnrollment $enrollment, EmailSequenceNextStep $step): string
    {
        $slug = $step === EmailSequenceNextStep::Followup
            ? $enrollment->followup_template_slug
            : $enrollment->nudge_template_slug;

        $raw = $this->templates->rawTemplate($slug);

        if ($raw['subject'] === '' && $raw['body'] === '') {
            $this->complete($enrollment, EmailSequenceExitReason::MissingTemplate);

            return 'exited';
        }

        $contact = $enrollment->contact;
        $personalized = $this->templates->applyTokens($raw['subject'], $raw['body'], $contact);
        $thread = $enrollment->thread;
        $replyTo = $this->latestOutboundMessage($thread) ?? $enrollment->coldMessage;
        $subject = 'Re: '.$enrollment->cold_subject;

        try {
            $this->outreachEmailService->send(
                contact: $contact,
                subject: $subject,
                bodyHtml: $personalized['body'],
                campaignId: $enrollment->campaign_id,
                userId: $enrollment->created_by,
                thread: $thread,
                replyTo: $replyTo,
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->complete($enrollment, EmailSequenceExitReason::SendFailed);

            throw $exception;
        }

        if ($step === EmailSequenceNextStep::Followup) {
            $enrollment->update([
                'followup_sent_at' => now(),
                'next_step' => EmailSequenceNextStep::Nudge,
                'next_action_at' => BusinessDays::addAfter(
                    $enrollment->enrolled_at,
                    (int) config('outreach.sequence.nudge_business_days', 8),
                ),
            ]);
        } else {
            $enrollment->update([
                'nudge_sent_at' => now(),
                'next_step' => EmailSequenceNextStep::Exit,
                'next_action_at' => BusinessDays::addAfter(
                    $enrollment->enrolled_at,
                    (int) config('outreach.sequence.exit_business_days', 15),
                ),
            ]);
        }

        return 'sent';
    }

    protected function exitSequence(EmailSequenceEnrollment $enrollment): string
    {
        $contact = $enrollment->contact;

        if ($this->isHotOpen($enrollment)) {
            $this->complete($enrollment, EmailSequenceExitReason::HotOpens);
            $this->createHotOpenFollowUp($contact);

            return 'exited';
        }

        $this->complete($enrollment, EmailSequenceExitReason::QuietLost);

        if ($contact->status === ContactStatus::Contacted || $contact->status === ContactStatus::New) {
            $contact->update(['status' => ContactStatus::Lost]);
        }

        return 'exited';
    }

    public function isHotOpen(EmailSequenceEnrollment $enrollment): bool
    {
        $minTotal = (int) config('outreach.sequence.hot_open_min_total_opens', 5);
        $minUnique = (int) config('outreach.sequence.hot_open_min_unique_emails', 2);

        $stats = EmailMessage::query()
            ->where('email_thread_id', $enrollment->email_thread_id)
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->where(fn ($query) => $query
                ->whereNotNull('opened_at')
                ->orWhere('open_count', '>', 0))
            ->selectRaw('count(*) as emails_opened, coalesce(sum(open_count), 0) as total_opens')
            ->first();

        $emailsOpened = (int) ($stats?->emails_opened ?? 0);
        $totalOpens = (int) ($stats?->total_opens ?? 0);

        return $totalOpens >= $minTotal || $emailsOpened >= $minUnique;
    }

    protected function createHotOpenFollowUp(Contact $contact): void
    {
        $note = 'Sequence complete — hot opens, no reply. Switch to LinkedIn / WhatsApp (do not send more email).';

        $exists = FollowUp::query()
            ->where('contact_id', $contact->id)
            ->where('completed', false)
            ->where('note', $note)
            ->exists();

        if ($exists) {
            return;
        }

        FollowUp::query()->create([
            'contact_id' => $contact->id,
            'due_date' => now()->toDateString(),
            'note' => $note,
            'completed' => false,
        ]);
    }

    protected function shouldStopForStatus(?ContactStatus $status): bool
    {
        return in_array($status, [
            ContactStatus::Responded,
            ContactStatus::Qualified,
            ContactStatus::Won,
            ContactStatus::Lost,
        ], true);
    }

    protected function contactHasInboundReply(Contact $contact): bool
    {
        return EmailMessage::query()
            ->where('direction', EmailMessageDirection::Inbound)
            ->whereHas('thread', fn ($query) => $query->where('contact_id', $contact->id))
            ->exists();
    }

    protected function latestOutboundMessage(?EmailThread $thread): ?EmailMessage
    {
        if (! $thread) {
            return null;
        }

        return $thread->messages()
            ->where('direction', EmailMessageDirection::Outbound)
            ->where('delivery_status', EmailDeliveryStatus::Sent)
            ->latest('id')
            ->first();
    }
}
