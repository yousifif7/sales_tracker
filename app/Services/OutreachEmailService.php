<?php

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use App\Enums\EmailThreadStatus;
use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Mail\ContactOutreachMail;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Interaction;
use App\Support\HtmlContent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class OutreachEmailService
{
    /**
     * @return array{thread: EmailThread, message: EmailMessage}
     */
    public function send(
        Contact $contact,
        string $subject,
        string $bodyHtml,
        ?int $campaignId = null,
        ?int $userId = null,
        ?EmailThread $thread = null,
        ?EmailMessage $replyTo = null,
    ): array {
        abort_unless(filled($contact->email), 422, 'This contact has no email address.');

        $bodyHtml = HtmlContent::sanitize($bodyHtml);
        $bodyText = HtmlContent::toPlainText($bodyHtml);
        $fromEmail = (string) config('mail.from.address');

        $thread ??= $this->resolveThread($contact, $subject, $campaignId);
        $messageId = $this->generateMessageId();
        $trackingToken = Str::random(40);

        $inReplyTo = $replyTo?->message_id;
        $references = $this->buildReferences($thread, $replyTo);

        $interaction = Interaction::query()->create([
            'contact_id' => $contact->id,
            'campaign_id' => $campaignId ?? $thread->campaign_id,
            'channel' => InteractionChannel::Email,
            'direction' => InteractionDirection::Outbound,
            'content' => "Subject: {$subject}\n\n{$bodyText}",
            'sent_at' => now(),
            'created_by' => $userId,
        ]);

        $message = EmailMessage::query()->create([
            'email_thread_id' => $thread->id,
            'interaction_id' => $interaction->id,
            'created_by' => $userId,
            'direction' => EmailMessageDirection::Outbound,
            'from_email' => $fromEmail,
            'to_email' => $contact->email,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'references' => $references,
            'tracking_token' => $trackingToken,
            'delivery_status' => EmailDeliveryStatus::Failed,
            'sent_at' => null,
        ]);

        try {
            Mail::mailer(config('mail.default'))
                ->to($contact->email)
                ->send(new ContactOutreachMail(
                    contact: $contact,
                    emailSubject: $subject,
                    emailBody: $bodyHtml,
                    messageId: $messageId,
                    inReplyTo: $inReplyTo,
                    referencesHeader: $references,
                    trackingToken: $trackingToken,
                ));

            $message->update([
                'delivery_status' => EmailDeliveryStatus::Sent,
                'sent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            throw $exception;
        }

        $thread->update([
            'subject' => $thread->subject ?: $subject,
            'campaign_id' => $campaignId ?? $thread->campaign_id,
            'status' => EmailThreadStatus::AwaitingReply,
            'last_message_at' => now(),
        ]);

        if ($contact->status === ContactStatus::New) {
            $contact->update(['status' => ContactStatus::Contacted]);
        }

        return [
            'thread' => $thread->fresh(['messages', 'contact']),
            'message' => $message->fresh(),
        ];
    }

    public function resolveThread(Contact $contact, string $subject, ?int $campaignId = null): EmailThread
    {
        $normalized = $this->normalizeSubject($subject);

        $existing = EmailThread::query()
            ->where('contact_id', $contact->id)
            ->whereIn('status', [
                EmailThreadStatus::Open->value,
                EmailThreadStatus::AwaitingReply->value,
                EmailThreadStatus::Responded->value,
            ])
            ->latest('last_message_at')
            ->get()
            ->first(fn (EmailThread $thread) => $this->normalizeSubject($thread->subject) === $normalized);

        if ($existing) {
            return $existing;
        }

        return EmailThread::query()->create([
            'contact_id' => $contact->id,
            'campaign_id' => $campaignId,
            'subject' => $subject,
            'status' => EmailThreadStatus::Open,
            'last_message_at' => now(),
        ]);
    }

    public function generateMessageId(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'sales-tracker.local';

        return sprintf('<%s@%s>', Str::uuid()->toString(), $host);
    }

    public function buildReferences(EmailThread $thread, ?EmailMessage $replyTo = null): ?string
    {
        $ids = $thread->messages()
            ->whereNotNull('message_id')
            ->orderBy('id')
            ->pluck('message_id')
            ->map(fn (string $id) => str_starts_with($id, '<') ? $id : '<'.$id.'>')
            ->all();

        if ($replyTo?->message_id) {
            $replyId = str_starts_with($replyTo->message_id, '<')
                ? $replyTo->message_id
                : '<'.$replyTo->message_id.'>';

            if (! in_array($replyId, $ids, true)) {
                $ids[] = $replyId;
            }
        }

        return $ids === [] ? null : implode(' ', $ids);
    }

    public function normalizeSubject(string $subject): string
    {
        $subject = preg_replace('/^(re|fwd|fw)\s*:\s*/i', '', trim($subject)) ?? trim($subject);

        while (preg_match('/^(re|fwd|fw)\s*:\s*/i', $subject)) {
            $subject = preg_replace('/^(re|fwd|fw)\s*:\s*/i', '', $subject) ?? $subject;
        }

        return strtolower(trim($subject));
    }
}
