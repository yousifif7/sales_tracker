<?php

namespace App\Http\Controllers;

use App\Http\Requests\FollowUpRequest;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Support\Permissions;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_VIEW);

        $today = CarbonImmutable::today();

        return view('follow-ups.index', [
            'followUps' => FollowUp::query()
                ->with('contact')
                ->when($request->string('scope')->value() === 'today', fn ($query) => $query->whereDate('due_date', $today))
                ->when($request->string('scope')->value() === 'week', fn ($query) => $query->whereBetween('due_date', [$today, $today->endOfWeek()]))
                ->when($request->filled('completed'), fn ($query) => $query->where('completed', (bool) $request->boolean('completed')))
                ->orderBy('completed')
                ->orderBy('due_date')
                ->paginate(15)
                ->withQueryString(),
            'contacts' => Contact::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_CREATE);

        return view('follow-ups.create', [
            'followUp' => new FollowUp(),
            'contacts' => Contact::query()->orderBy('name')->get(),
        ]);
    }

    public function store(FollowUpRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_CREATE);

        FollowUp::query()->create([
            ...$request->validated(),
            'completed' => $request->boolean('completed'),
        ]);

        return redirect()
            ->route('follow-ups.index')
            ->with('status', 'Follow-up created successfully.');
    }

    public function edit(FollowUp $followUp): View
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_UPDATE);

        return view('follow-ups.edit', [
            'followUp' => $followUp,
            'contacts' => Contact::query()->orderBy('name')->get(),
        ]);
    }

    public function update(FollowUpRequest $request, FollowUp $followUp): RedirectResponse
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_UPDATE);

        $followUp->update([
            ...$request->validated(),
            'completed' => $request->boolean('completed'),
        ]);

        return redirect()
            ->route('follow-ups.index')
            ->with('status', 'Follow-up updated successfully.');
    }

    public function toggle(FollowUp $followUp): RedirectResponse
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_UPDATE);

        $followUp->update([
            'completed' => ! $followUp->completed,
        ]);

        return back()->with('status', 'Follow-up status updated.');
    }

    public function destroy(FollowUp $followUp): RedirectResponse
    {
        $this->authorizePermission(Permissions::FOLLOW_UPS_DELETE);

        $followUp->delete();

        return redirect()
            ->route('follow-ups.index')
            ->with('status', 'Follow-up deleted successfully.');
    }
}
