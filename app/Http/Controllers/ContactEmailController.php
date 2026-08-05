<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkContactEmailComposeRequest;
use App\Http\Requests\SendBulkContactEmailRequest;
use App\Http\Requests\SendContactEmailRequest;
use App\Jobs\SendOutreachEmailJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\OutreachEmailService;
use App\Support\CurrentUserResolver;
use App\Support\OutreachTemplateRenderer;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class ContactEmailController extends Controller
{
    public function create(Contact $contact, OutreachTemplateRenderer $templates): View
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        abort_unless(filled($contact->email), 422, 'This contact has no email address.');

        $templateKey = request('template');
        $rendered = filled($templateKey)
            ? $templates->render((string) $templateKey, $contact)
            : ['subject' => old('subject', ''), 'body' => old('body', '')];

        return view('contacts.email', [
            'contact' => $contact,
            'campaigns' => Campaign::query()->orderBy('name')->get(),
            'templates' => $templates->options(),
            'selectedTemplate' => $templateKey,
            'subject' => old('subject', $rendered['subject']),
            'body' => old('body', $rendered['body']),
        ]);
    }

    public function store(
        SendContactEmailRequest $request,
        Contact $contact,
        CurrentUserResolver $currentUserResolver,
        OutreachEmailService $outreachEmailService,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        abort_unless(filled($contact->email), 422, 'This contact has no email address.');

        try {
            $result = $outreachEmailService->send(
                contact: $contact,
                subject: $request->validated('subject'),
                bodyHtml: $request->validated('body'),
                campaignId: $request->validated('campaign_id'),
                userId: $currentUserResolver->id(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['email' => 'SMTP send failed: '.$exception->getMessage()]);
        }

        return redirect()
            ->route('email-threads.show', $result['thread'])
            ->with('status', "Email sent to {$contact->email}. Tracking Sent / Opened / Responded on this thread.");
    }

    public function createBulk(
        BulkContactEmailComposeRequest $request,
        OutreachTemplateRenderer $templates,
    ): View|RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        [$recipients, $skippedCount] = $this->resolveBulkRecipients($request->validated('contact_ids'));

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('contacts.index')
                ->withErrors(['email' => 'None of the selected contacts have an email address.']);
        }

        $templateKey = $request->validated('template');
        $raw = filled($templateKey)
            ? $templates->rawTemplate((string) $templateKey)
            : ['subject' => old('subject', ''), 'body' => old('body', '')];

        return view('contacts.email-bulk', [
            'recipients' => $recipients,
            'skippedCount' => $skippedCount,
            'campaigns' => Campaign::query()->orderBy('name')->get(),
            'templates' => $templates->options(),
            'selectedTemplate' => $templateKey,
            'subject' => old('subject', $raw['subject']),
            'body' => old('body', $raw['body']),
        ]);
    }

    public function storeBulk(
        SendBulkContactEmailRequest $request,
        CurrentUserResolver $currentUserResolver,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        [$recipients, $skippedCount] = $this->resolveBulkRecipients($request->validated('contact_ids'));

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('contacts.index')
                ->withErrors(['email' => 'None of the selected contacts have an email address.']);
        }

        $subject = $request->validated('subject');
        $body = $request->validated('body');
        $campaignId = $request->validated('campaign_id');
        $userId = $currentUserResolver->id();
        $delaySeconds = 0;

        foreach ($recipients as $contact) {
            SendOutreachEmailJob::dispatch(
                contactId: $contact->id,
                subject: $subject,
                bodyHtml: $body,
                campaignId: $campaignId,
                userId: $userId,
            )->delay(now()->addSeconds($delaySeconds));

            $delaySeconds += 3;
        }

        $queued = $recipients->count();
        $message = "Queued {$queued} follow-up ".str('email')->plural($queued).'.';

        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} skipped: no email).";
        }

        return redirect()
            ->route('contacts.index')
            ->with('status', $message);
    }

    /**
     * @param  array<int, int|string>  $contactIds
     * @return array{0: Collection<int, Contact>, 1: int}
     */
    private function resolveBulkRecipients(array $contactIds): array
    {
        $contacts = Contact::query()
            ->whereIn('id', $contactIds)
            ->orderBy('name')
            ->get();

        $recipients = $contacts->filter(fn (Contact $contact) => filled($contact->email))->values();
        $skippedCount = $contacts->count() - $recipients->count();

        return [$recipients, $skippedCount];
    }
}
