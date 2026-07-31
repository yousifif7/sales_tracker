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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\Attachment;
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

        $this->purgeEmptyGhostThreads();

        try {
            $client = (new ClientManager([
                'options' => [
                    'fetch_body' => true,
                    'fetch_flags' => true,
                    'fetch_order' => 'desc',
                    'dispositions' => ['attachment'],
                ],
            ]))->make([
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

            $messages = $folder->messages()
                ->since(now()->subDays(14)->format('d M Y'))
                ->leaveUnread()
                ->setFetchOrder('desc')
                ->limit((int) config('imap.sync_limit', 100), 1)
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

    /**
     * Remove empty "Responded" ghosts created by older sync bugs.
     */
    protected function purgeEmptyGhostThreads(): void
    {
        EmailThread::withTrashed()
            ->whereDoesntHave('messages')
            ->each(function (EmailThread $thread): void {
                $thread->forceDelete();
            });
    }

    protected function importMessage(Message $message): string
    {
        $folder = (string) config('imap.folder', 'INBOX');
        $uid = (string) $message->getUid();

        // Already imported (including on a trashed thread) — never recreate.
        $existingByUid = EmailMessage::query()
            ->where('imap_folder', $folder)
            ->where('imap_uid', $uid)
            ->first();

        if ($existingByUid) {
            return $this->backfillEmptyBody($existingByUid, $message);
        }

        $rawMessageId = $this->headerString($message, 'Message-ID') ?: $this->headerString($message, 'Message-Id');
        $messageId = EmailMessage::normalizeMessageId($rawMessageId);

        if ($messageId) {
            $existingByMessageId = $this->findStoredMessage($messageId);

            if ($existingByMessageId) {
                if (! filled($existingByMessageId->imap_uid)) {
                    $existingByMessageId->update([
                        'imap_uid' => $uid,
                        'imap_folder' => $folder,
                    ]);
                }

                return $this->backfillEmptyBody($existingByMessageId, $message);
            }
        }

        $fromEmail = strtolower((string) ($message->getFrom()->first()?->mail ?? ''));
        if ($fromEmail === '') {
            return 'skipped';
        }

        $ourAddress = strtolower((string) config('mail.from.address'));
        if ($ourAddress !== '' && $fromEmail === $ourAddress) {
            return 'skipped';
        }

        $contact = Contact::query()->whereRaw('LOWER(email) = ?', [$fromEmail])->first();
        if (! $contact) {
            return 'skipped';
        }

        $inReplyTo = EmailMessage::normalizeMessageId($this->headerString($message, 'In-Reply-To'));
        $references = $this->headerString($message, 'References');

        // Strict: only import replies that belong to an active parent thread we already have.
        $thread = $this->matchActiveParentThread($contact, $inReplyTo, $references);
        if (! $thread) {
            $subject = (string) ($message->getSubject() ?: '');
            $thread = $this->matchActiveThreadBySubject($contact, $subject);
        }
        if (! $thread) {
            return 'skipped';
        }

        $subject = (string) ($message->getSubject() ?: $thread->subject);
        [$html, $text] = $this->extractBodies($message);

        $receivedAt = now();
        try {
            $dateValue = $message->getDate();
            if ($dateValue) {
                $receivedAt = Carbon::parse((string) $dateValue);
            }
        } catch (Throwable) {
            $receivedAt = now();
        }

        DB::transaction(function () use (
            $contact,
            $thread,
            $subject,
            $html,
            $text,
            $messageId,
            $inReplyTo,
            $references,
            $fromEmail,
            $message,
            $uid,
            $folder,
            $receivedAt,
        ): void {
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
                'body_html' => $html !== '' ? HtmlContent::sanitizeInbound($html) : null,
                'body_text' => $text !== '' ? $text : null,
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
                'has_unread' => true,
            ]);

            if ($contact->status !== ContactStatus::Won && $contact->status !== ContactStatus::Lost) {
                $contact->update(['status' => ContactStatus::Responded]);
            }
        });

        return 'imported';
    }

    /**
     * Match only an active (non-trashed) parent thread via In-Reply-To / References.
     * Never creates threads and never restores trash.
     */
    protected function matchActiveParentThread(Contact $contact, ?string $inReplyTo, ?string $references): ?EmailThread
    {
        $candidateIds = collect();

        if ($inReplyTo) {
            $candidateIds->push($inReplyTo, $this->angle($inReplyTo));
        }

        foreach ($this->referenceIds($references) as $ref) {
            $candidateIds->push($ref, $this->angle($ref));
        }

        $candidateIds = $candidateIds->filter()->unique()->values();

        if ($candidateIds->isEmpty()) {
            return null;
        }

        $parent = EmailMessage::query()
            ->where(function ($q) use ($candidateIds): void {
                $q->whereIn('message_id', $candidateIds->all());

                // Case-insensitive fallback — some servers rewrite casing.
                foreach ($candidateIds as $id) {
                    $normalized = EmailMessage::normalizeMessageId($id);
                    if ($normalized) {
                        $q->orWhereRaw('LOWER(TRIM(BOTH \'<>\' FROM message_id)) = ?', [$normalized]);
                    }
                }
            })
            ->whereHas('thread', fn ($q) => $q->where('contact_id', $contact->id))
            ->latest('id')
            ->first();

        if (! $parent) {
            return null;
        }

        return EmailThread::query()->find($parent->email_thread_id);
    }

    /**
     * Last-resort match when SMTP rewrote Message-IDs: same contact + same subject
     * on an active thread that already has an outbound message we sent.
     * Never creates threads and never touches trash.
     */
    protected function matchActiveThreadBySubject(Contact $contact, string $subject): ?EmailThread
    {
        if (! filled($subject)) {
            return null;
        }

        $normalized = app(OutreachEmailService::class)->normalizeSubject($subject);

        return EmailThread::query()
            ->where('contact_id', $contact->id)
            ->whereHas('messages', fn ($q) => $q->where('direction', EmailMessageDirection::Outbound->value))
            ->latest('last_message_at')
            ->get()
            ->first(fn (EmailThread $thread) => app(OutreachEmailService::class)->normalizeSubject($thread->subject) === $normalized);
    }

    protected function findStoredMessage(string $messageId): ?EmailMessage
    {
        $angled = $this->angle($messageId);

        return EmailMessage::query()
            ->where(function ($q) use ($messageId, $angled): void {
                $q->where('message_id', $angled)
                    ->orWhere('message_id', $messageId)
                    ->orWhereRaw('LOWER(TRIM(BOTH \'<>\' FROM message_id)) = ?', [$messageId]);
            })
            ->first();
    }

    protected function backfillEmptyBody(EmailMessage $existing, Message $message): string
    {
        $hasHtml = filled($existing->body_html) && trim(strip_tags((string) $existing->body_html)) !== '';
        $hasText = filled($existing->body_text);

        if ($hasHtml || $hasText) {
            return 'skipped';
        }

        // Do not resurrect trashed threads just to backfill.
        $thread = EmailThread::query()->find($existing->email_thread_id);
        if (! $thread) {
            return 'skipped';
        }

        [$html, $text] = $this->extractBodies($message);

        if ($html === '' && $text === '') {
            return 'skipped';
        }

        $existing->update([
            'body_html' => $html !== '' ? HtmlContent::sanitizeInbound($html) : $existing->body_html,
            'body_text' => $text !== '' ? $text : $existing->body_text,
        ]);

        if ($existing->interaction_id && filled($text)) {
            $existing->interaction?->update([
                'content' => "Subject: {$existing->subject}\n\n{$text}",
            ]);
        }

        return 'imported';
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function extractBodies(Message $message): array
    {
        $html = trim((string) $message->getHTMLBody());
        $text = trim((string) $message->getTextBody());

        if ($html === '' && $text === '') {
            try {
                $message->parseBody();
                $html = trim((string) $message->getHTMLBody());
                $text = trim((string) $message->getTextBody());
            } catch (Throwable) {
                // Keep falling through.
            }
        }

        if ($html === '' && $text === '') {
            $bodies = $message->getBodies();
            $html = trim((string) ($bodies['html'] ?? ''));
            $text = trim((string) ($bodies['text'] ?? $bodies['plain'] ?? ''));
        }

        if ($html === '' && $text === '') {
            try {
                foreach ($message->getAttachments() as $attachment) {
                    [$partHtml, $partText] = $this->bodiesFromAttachment($attachment);
                    if ($partHtml !== '' && $html === '') {
                        $html = $partHtml;
                    }
                    if ($partText !== '' && $text === '') {
                        $text = $partText;
                    }
                    if ($html !== '' && $text !== '') {
                        break;
                    }
                }
            } catch (Throwable) {
                // Ignore.
            }
        }

        if ($html === '' && $text === '') {
            $raw = trim((string) $message->getRawBody());
            if ($raw !== '') {
                if (preg_match('/<(html|body|div|p|br|span|table)\b/i', $raw)) {
                    $html = $raw;
                } else {
                    $text = $raw;
                }
            }
        }

        if ($html === '' && $text !== '') {
            $html = HtmlContent::plainToHtml($text);
        }

        if ($text === '' && $html !== '') {
            $text = HtmlContent::toPlainText($html);
        }

        return [$html, $text];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function bodiesFromAttachment(Attachment $attachment): array
    {
        $content = trim((string) $attachment->getContent());
        if ($content === '') {
            return ['', ''];
        }

        $mime = strtolower((string) ($attachment->getContentType() ?? $attachment->getMimeType() ?? ''));
        $name = strtolower((string) ($attachment->getName() ?? ''));

        $looksHtml = str_contains($mime, 'html')
            || str_ends_with($name, '.html')
            || str_ends_with($name, '.htm')
            || (bool) preg_match('/<(html|body|div|p|br|span|table)\b/i', $content);

        $looksText = str_contains($mime, 'text/plain')
            || str_ends_with($name, '.txt')
            || (! $looksHtml && ! str_contains($mime, 'image/') && ! str_contains($mime, 'application/'));

        if ($looksHtml) {
            return [$content, HtmlContent::toPlainText($content)];
        }

        if ($looksText) {
            return [HtmlContent::plainToHtml($content), $content];
        }

        return ['', ''];
    }

    /**
     * @return list<string>
     */
    protected function referenceIds(?string $references): array
    {
        if (! filled($references)) {
            return [];
        }

        $ids = [];

        preg_match_all('/<[^>]+>/', $references, $angled);
        foreach ($angled[0] ?? [] as $ref) {
            $normalized = EmailMessage::normalizeMessageId($ref);
            if ($normalized) {
                $ids[] = $normalized;
            }
        }

        foreach (preg_split('/\s+/', $references) ?: [] as $token) {
            $normalized = EmailMessage::normalizeMessageId($token);
            if ($normalized) {
                $ids[] = $normalized;
            }
        }

        return array_values(array_unique($ids));
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
