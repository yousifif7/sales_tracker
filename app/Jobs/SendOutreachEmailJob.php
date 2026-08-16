<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Services\OutreachEmailService;
use App\Services\OutreachSequenceService;
use App\Support\OutreachTemplateRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class SendOutreachEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public int $contactId,
        public string $subject,
        public string $bodyHtml,
        public ?int $campaignId = null,
        public ?int $userId = null,
        public bool $enrollInSequence = false,
    ) {
    }

    public function handle(
        OutreachEmailService $outreachEmailService,
        OutreachTemplateRenderer $templates,
        OutreachSequenceService $sequences,
    ): void {
        $contact = Contact::query()->find($this->contactId);

        if (! $contact || ! filled($contact->email)) {
            return;
        }

        $personalized = $templates->applyTokens($this->subject, $this->bodyHtml, $contact);

        try {
            $result = $outreachEmailService->send(
                contact: $contact,
                subject: $personalized['subject'],
                bodyHtml: $personalized['body'],
                campaignId: $this->campaignId,
                userId: $this->userId,
            );

            if ($this->enrollInSequence) {
                $sequences->enroll(
                    contact: $contact,
                    thread: $result['thread'],
                    coldMessage: $result['message'],
                    campaignId: $this->campaignId,
                    userId: $this->userId,
                );
            }
        } catch (Throwable $exception) {
            report($exception);

            throw $exception;
        }
    }
}
