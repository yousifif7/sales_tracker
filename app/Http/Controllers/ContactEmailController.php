<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendContactEmailRequest;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\OutreachEmailService;
use App\Support\OutreachTemplateRenderer;
use App\Support\CurrentUserResolver;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
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
}
