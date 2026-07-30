<?php

namespace App\Mail;

use App\Models\Contact;
use App\Support\HtmlContent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class ContactOutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $bodyHtml;

    public string $bodyText;

    public function __construct(
        public Contact $contact,
        public string $emailSubject,
        public string $emailBody,
        public string $messageId,
        public ?string $inReplyTo = null,
        public ?string $referencesHeader = null,
        public ?string $trackingToken = null,
    ) {
        $sanitized = HtmlContent::sanitize($emailBody);
        $html = $sanitized !== '' ? $sanitized : HtmlContent::plainToHtml($emailBody);
        $this->bodyHtml = $this->withTrackingPixel($html);
        $this->bodyText = HtmlContent::toPlainText($html);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            to: [$this->contact->email],
            using: [
                function (Email $message): void {
                    $message->getHeaders()->remove('Message-ID');
                    $message->getHeaders()->addIdHeader('Message-ID', trim($this->messageId, '<>'));

                    if (filled($this->inReplyTo)) {
                        $message->getHeaders()->addIdHeader('In-Reply-To', trim($this->inReplyTo, '<>'));
                    }

                    if (filled($this->referencesHeader)) {
                        $ids = collect(preg_split('/\s+/', $this->referencesHeader) ?: [])
                            ->map(fn (string $id) => trim($id, "<> \t"))
                            ->filter()
                            ->values()
                            ->all();

                        if ($ids !== []) {
                            $message->getHeaders()->remove('References');
                            $message->getHeaders()->addIdHeader('References', $ids);
                        }
                    }
                },
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: [
                'X-Sales-Tracker' => 'outreach',
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.outreach-html',
            text: 'emails.outreach-text',
            with: [
                'subject' => $this->emailSubject,
                'bodyHtml' => $this->bodyHtml,
                'bodyText' => $this->bodyText,
            ],
        );
    }

    protected function withTrackingPixel(string $html): string
    {
        if (! filled($this->trackingToken)) {
            return $html;
        }

        $url = route('email.tracking.open', ['token' => $this->trackingToken]);
        $pixel = '<img src="'.e($url).'" width="1" height="1" alt="" style="display:none;width:1px;height:1px;border:0;" />';

        if (str_contains(strtolower($html), '</body>')) {
            return preg_replace('/<\/body>/i', $pixel.'</body>', $html, 1) ?? ($html.$pixel);
        }

        return $html.$pixel;
    }
}
