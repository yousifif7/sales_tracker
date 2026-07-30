<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Enums\InteractionDirection;
use App\Enums\ResponseOutcome;
use App\Http\Requests\InteractionRequest;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Interaction;
use App\Support\CurrentUserResolver;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InteractionController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::INTERACTIONS_VIEW);

        return view('interactions.index', [
            'interactions' => Interaction::query()
                ->with(['contact', 'campaign', 'response', 'creator'])
                ->latest('sent_at')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::INTERACTIONS_CREATE);

        return view('interactions.create', [
            'interaction' => new Interaction(),
            'contacts' => Contact::query()->orderBy('name')->get(),
            'campaigns' => Campaign::query()->orderBy('name')->get(),
        ]);
    }

    public function store(InteractionRequest $request, CurrentUserResolver $currentUserResolver): RedirectResponse
    {
        $this->authorizePermission(Permissions::INTERACTIONS_CREATE);

        $interaction = Interaction::query()->create([
            ...$request->safe()->except('response'),
            'created_by' => $currentUserResolver->id(),
        ]);

        $this->markContactContactedIfNeeded($interaction);
        $this->persistResponseData($interaction, $request->validated('response', []));

        return redirect()
            ->route('contacts.show', $interaction->contact)
            ->with('status', 'Interaction logged successfully.');
    }

    public function edit(Interaction $interaction): View
    {
        $this->authorizePermission(Permissions::INTERACTIONS_UPDATE);

        $interaction->load('response');

        return view('interactions.edit', [
            'interaction' => $interaction,
            'contacts' => Contact::query()->orderBy('name')->get(),
            'campaigns' => Campaign::query()->orderBy('name')->get(),
        ]);
    }

    public function update(InteractionRequest $request, Interaction $interaction): RedirectResponse
    {
        $this->authorizePermission(Permissions::INTERACTIONS_UPDATE);

        $interaction->update($request->safe()->except('response'));
        $this->markContactContactedIfNeeded($interaction);
        $this->persistResponseData($interaction, $request->validated('response', []));

        return redirect()
            ->route('contacts.show', $interaction->contact)
            ->with('status', 'Interaction updated successfully.');
    }

    public function destroy(Interaction $interaction): RedirectResponse
    {
        $this->authorizePermission(Permissions::INTERACTIONS_DELETE);

        $contact = $interaction->contact;
        $interaction->delete();

        return redirect()
            ->route('contacts.show', $contact)
            ->with('status', 'Interaction deleted successfully.');
    }

    protected function markContactContactedIfNeeded(Interaction $interaction): void
    {
        $contact = $interaction->contact;

        if ($contact->status === ContactStatus::New && $interaction->direction === InteractionDirection::Outbound) {
            $contact->update(['status' => ContactStatus::Contacted]);
        }
    }

    /**
     * @param  array<string, mixed>  $responseData
     */
    protected function persistResponseData(Interaction $interaction, array $responseData): void
    {
        if (! filled($responseData['outcome'] ?? null)) {
            $interaction->response?->delete();

            return;
        }

        $response = $interaction->response()->updateOrCreate([], [
            'outcome' => $responseData['outcome'],
            'sentiment_notes' => $responseData['sentiment_notes'] ?? null,
            'follow_up_date' => $responseData['follow_up_date'] ?? null,
        ]);

        if (filled($response->follow_up_date)) {
            FollowUp::query()->updateOrCreate(
                [
                    'contact_id' => $interaction->contact_id,
                    'due_date' => $response->follow_up_date,
                ],
                [
                    'note' => 'Auto-created from interaction response.',
                    'completed' => false,
                ],
            );
        }

        $interaction->contact->update([
            'status' => match ($response->outcome) {
                ResponseOutcome::Interested => ContactStatus::Qualified,
                ResponseOutcome::NotInterested => ContactStatus::Lost,
                default => ContactStatus::Responded,
            },
        ]);
    }
}
