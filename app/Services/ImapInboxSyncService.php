<?php

namespace App\Services;

use App\Enums\ContactStatus;
use App\Enums\EmailMessageDirection;
use App\Enums\EmailThreadStatus;
use App\Enums\InteractionChannel;
use App\Enums\InteractionDirection;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\EmailThread;
use App\Models\Interaction;
use App\Support\HtmlContent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;
use Throwable;

class ImapInboxSyncService
{
    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function sync(): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $username = config('imap.username');
        $password = config('imap.password');

        if (! filled($username) || ! filled($password)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['IMAP credentials are not configured.'],
            ];
        }

        try {
            $client = (new ClientManager)->make([
                'host' => config('imap.host'),
                'port' => config('imap.port'),
                'encryption' => config('imap.encryption'),
                'validate_cert' => config('imap.validate_cert'),
                'username' => $username,
                'password' => $password,
                'protocol' => 'imap',
            ]);

            $client->connect();
            $folder = $client->getFolder(config('imap.folder', 'INBOX'));

            // Fetch recent messages (last 14 days) to catch replies.
            $messages = $folder->messages()
                ->since(now()->subDays(14)->format('d M Y'))
                ->leaveUnread()
                ->limit(50, 1)
                ->get();

            foreach ($messages as $message) {
                try {
                    $result = $this->importMessage($message);
                    if ($result === 'imported') {
                        $imported++;
                        if (config('imap.mark_as_read')) {
                            $message->setFlag('Seen');
                        }
                    } else {
                        $skipped++;
                    }
                } catch (Throwable $exception) {
                    report($exception);
                    $errors[] = $exception->getMessage();
                }
            }

            $client->disconnect();
        } catch (Throwable $exception) {
            report($exception);
            $errors[] = $exception->getMessage();
            Log::warning('IMAP sync failed', ['error' => $exception->getMessage()]);
        }

        return compact('imported', 'skipped', 'errors');
    }

    protected function importMessage(Message $message): string
    {
        $folder = (string) config('imap.folder', 'INBOX');
        $uid = (string) $message->getUid();

        if (EmailMessage::query()->where('imap_folder', $folder)->where('imap_uid', $uid)->exists()) {
            return 'skipped';
        }

        $rawMessageId = $this->headerString($message, 'Message-ID') ?: $this->headerString($message, 'Message-Id');
        $messageId = EmailMessage::normalizeMessageId($rawMessageId);

        if ($messageId && EmailMessage::query()->where('message_id', $this->angle($messageId))->exists()) {
            return 'skipped';
        }

        if ($messageId && EmailMessage::query()->where('message_id', $messageId)->exists()) {
            return 'skipped';
        }

        $fromEmail = strtolower((string) ($message->getFrom()->first()?->mail ?? ''));
        if ($fromEmail === '') {
            return 'skipped';
        }

        // Ignore mail from our own mailbox.
        $ourAddress = strtolower((string) config('mail.from.address'));
        if ($ourAddress !== '' && $fromEmail === $ourAddress) {
            return 'skipped';
        }

        $contact = Contact::query()->whereRaw('LOWER(email) = ?', [$fromEmail])->first();
        if (! $contact) {
            return 'skipped';
        }

        $subject = (string) ($message->getSubject() ?: '(no subject)');
        $inReplyTo = EmailMessage::normalizeMessageId($this->headerString($message, 'In-Reply-To'));
        $references = $this->headerString($message, 'References');

        $thread = $this->matchThread($contact, $inReplyTo, $references, $subject);

        $html = (string) ($message->getHTMLBody() ?: '');
        $text = (string) ($message->getTextBody() ?: '');

        if ($html === '' && $text !== '') {
            $html = HtmlContent::plainToHtml($text);
        }

        if ($text === '' && $html !== '') {
            $text = HtmlContent::toPlainText($html);
        }

        $receivedAt = now();
        try {
            $dateValue = $message->getDate();
            if ($dateValue) {
                $receivedAt = Carbon::parse((string) $dateValue);
            }
        } catch (Throwable) {
            $receivedAt = now();
        }

        $interaction = Interaction::query()->create([
            'contact_id' => $contact->id,
            'campaign_id' => $thread->campaign_id,
            'channel' => InteractionChannel::Email,
            'direction' => InteractionDirection::Inbound,
            'content' => "Subject: {$subject}\n\n{$text}",
            'sent_at' => $receivedAt,
            'created_by' => null,
        ]);

        EmailMessage::query()->create([
            'email_thread_id' => $thread->id,
            'interaction_id' => $interaction->id,
            'created_by' => null,
            'direction' => EmailMessageDirection::Inbound,
            'from_email' => $fromEmail,
            'to_email' => (string) (config('mail.from.address') ?: $this->headerString($message, 'To')),
            'subject' => $subject,
            'body_html' => $html !== '' ? HtmlContent::sanitize($html) : null,
            'body_text' => $text,
            'message_id' => $messageId ? $this->angle($messageId) : null,
            'in_reply_to' => $inReplyTo ? $this->angle($inReplyTo) : null,
            'references' => $references,
            'imap_uid' => $uid,
            'imap_folder' => $folder,
            'tracking_token' => null,
            'delivery_status' => null,
            'sent_at' => null,
            'received_at' => $receivedAt,
        ]);

        $thread->update([
            'status' => EmailThreadStatus::Responded,
            'last_message_at' => $receivedAt,
        ]);

        if ($contact->status !== ContactStatus::Won && $contact->status !== ContactStatus::Lost) {
            $contact->update(['status' => ContactStatus::Responded]);
        }

        return 'imported';
    }

    protected function matchThread(Contact $contact, ?string $inReplyTo, ?string $references, string $subject): EmailThread
    {
        $candidateIds = collect();

        if ($inReplyTo) {
            $candidateIds->push($inReplyTo, $this->angle($inReplyTo));
        }

        if (filled($references)) {
            preg_match_all('/<[^>]+>/', $references, $matches);
            foreach ($matches[0] ?? [] as $ref) {
                $candidateIds->push(EmailMessage::normalizeMessageId($ref), $ref);
            }
        }

        $candidateIds = $candidateIds->filter()->unique()->values();

        if ($candidateIds->isNotEmpty()) {
            $parent = EmailMessage::query()
                ->whereIn('message_id', $candidateIds->all())
                ->whereHas('thread', fn ($q) => $q->withTrashed()->where('contact_id', $contact->id))
                ->latest('id')
                ->first();

            if ($parent) {
                $thread = EmailThread::withTrashed()->find($parent->email_thread_id);
                if ($thread) {
                    if ($thread->trashed()) {
                        $thread->restore();
                    }

                    return $thread;
                }
            }
        }

        $normalized = app(OutreachEmailService::class)->normalizeSubject($subject);

        $existing = EmailThread::withTrashed()
            ->where('contact_id', $contact->id)
            ->latest('last_message_at')
            ->get()
            ->first(fn (EmailThread $thread) => app(OutreachEmailService::class)->normalizeSubject($thread->subject) === $normalized);

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return EmailThread::query()->create([
            'contact_id' => $contact->id,
            'campaign_id' => null,
            'subject' => $subject,
            'status' => EmailThreadStatus::Responded,
            'last_message_at' => now(),
        ]);
    }

    protected function headerString(Message $message, string $name): ?string
    {
        try {
            $header = $message->getHeader()->get($name);
            if ($header === null) {
                return null;
            }

            if (is_array($header)) {
                $header = $header[0] ?? null;
            }

            $value = is_object($header) && method_exists($header, 'toString')
                ? $header->toString()
                : (string) $header;

            return filled($value) ? trim($value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function angle(string $id): string
    {
        $id = trim($id, " \t\n\r\0\x0B<>");

        return '<'.$id.'>';
    }
}
