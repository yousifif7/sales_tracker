<?php

namespace App\Http\Controllers;

use App\Enums\EmailSequenceExitReason;
use App\Models\Contact;
use App\Services\OutreachSequenceService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;

class ContactSequenceController extends Controller
{
    public function cancel(
        Contact $contact,
        OutreachSequenceService $sequences,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $enrollment = $sequences->activeEnrollmentFor($contact);

        if (! $enrollment) {
            return redirect()
                ->route('contacts.show', $contact)
                ->withErrors(['sequence' => 'No active email sequence for this contact.']);
        }

        $sequences->complete($enrollment, EmailSequenceExitReason::Cancelled);

        return redirect()
            ->back(fallback: route('contacts.show', $contact))
            ->with('status', 'Email sequence cancelled. No further automated follow-ups will send.');
    }
}
