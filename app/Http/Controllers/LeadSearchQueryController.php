<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadSearchRequest;
use App\Models\Contact;
use App\Models\LeadSearchPreset;
use App\Models\LeadSearchQuery;
use App\Support\CurrentUserResolver;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadSearchQueryController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCHES_VIEW);

        return view('lead-searches.index', [
            'queries' => LeadSearchQuery::query()
                ->with('creator')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCHES_CREATE);

        $presetKey = request('preset');
        $presetCriteria = '';

        if (filled($presetKey)) {
            $presetCriteria = (string) (LeadSearchPreset::query()
                ->active()
                ->where('slug', $presetKey)
                ->value('criteria') ?? '');
        }

        return view('lead-searches.create', [
            'presetCriteria' => old('criteria', $presetCriteria),
            'presets' => LeadSearchPreset::query()->active()->ordered()->get(),
        ]);
    }

    public function store(LeadSearchRequest $request, CurrentUserResolver $currentUserResolver): RedirectResponse
    {
        $this->authorizePermission(Permissions::LEAD_SEARCHES_CREATE);

        LeadSearchQuery::query()->create([
            'criteria' => $request->validated('criteria'),
            'created_by' => $currentUserResolver->id(),
            'raw_results' => null,
        ]);

        return redirect()
            ->route('lead-searches.index')
            ->with('status', 'Lead search queued successfully.');
    }

    public function show(LeadSearchQuery $leadSearch): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCHES_VIEW);

        $results = $leadSearch->raw_results['results'] ?? [];

        $matchedContacts = collect($results)->map(function (array $lead) {
            if (filled($lead['email'] ?? null)) {
                $byEmail = Contact::query()->where('email', $lead['email'])->first();
                if ($byEmail) {
                    return $byEmail;
                }
            }

            return Contact::query()
                ->where('name', $lead['name'] ?? null)
                ->when(filled($lead['company'] ?? null), fn ($query) => $query->where('company', $lead['company']))
                ->first();
        });

        return view('lead-searches.show', [
            'query' => $leadSearch->load('creator'),
            'matchedContacts' => $matchedContacts,
        ]);
    }

    public function destroy(LeadSearchQuery $leadSearch): RedirectResponse
    {
        $this->authorizePermission(Permissions::LEAD_SEARCHES_DELETE);

        $leadSearch->delete();

        return redirect()
            ->route('lead-searches.index')
            ->with('status', 'Lead search deleted.');
    }
}
