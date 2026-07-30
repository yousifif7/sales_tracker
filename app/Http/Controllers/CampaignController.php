<?php

namespace App\Http\Controllers;

use App\Enums\CampaignChannel;
use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_VIEW);

        return view('campaigns.index', [
            'campaigns' => Campaign::query()
                ->withCount('interactions')
                ->latest()
                ->paginate(12),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_CREATE);

        return view('campaigns.create', [
            'campaign' => new Campaign(['channel' => CampaignChannel::Email]),
            'channelOptions' => CampaignChannel::options(),
        ]);
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_CREATE);

        $campaign = Campaign::query()->create($request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign): View
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_VIEW);

        $campaign->load(['interactions.contact', 'interactions.response']);

        return view('campaigns.show', [
            'campaign' => $campaign,
        ]);
    }

    public function edit(Campaign $campaign): View
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_UPDATE);

        return view('campaigns.edit', [
            'campaign' => $campaign,
            'channelOptions' => CampaignChannel::options(),
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_UPDATE);

        $campaign->update($request->validated());

        return redirect()
            ->route('campaigns.show', $campaign)
            ->with('status', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $this->authorizePermission(Permissions::CAMPAIGNS_DELETE);

        $campaign->delete();

        return redirect()
            ->route('campaigns.index')
            ->with('status', 'Campaign deleted successfully.');
    }
}
