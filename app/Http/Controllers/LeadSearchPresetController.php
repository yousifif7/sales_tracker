<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadSearchPresetRequest;
use App\Models\LeadSearchPreset;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeadSearchPresetController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_VIEW);

        return view('lead-search-presets.index', [
            'presets' => LeadSearchPreset::query()->ordered()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_CREATE);

        return view('lead-search-presets.create', [
            'preset' => new LeadSearchPreset(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(LeadSearchPresetRequest $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_CREATE);

        LeadSearchPreset::query()->create($this->payload($request));

        return redirect()
            ->route('lead-search-presets.index')
            ->with('status', 'AI prompt created.');
    }

    public function edit(LeadSearchPreset $leadSearchPreset): View
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_UPDATE);

        return view('lead-search-presets.edit', [
            'preset' => $leadSearchPreset,
        ]);
    }

    public function update(LeadSearchPresetRequest $request, LeadSearchPreset $leadSearchPreset): RedirectResponse
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_UPDATE);

        $leadSearchPreset->update($this->payload($request, $leadSearchPreset));

        return redirect()
            ->route('lead-search-presets.index')
            ->with('status', 'AI prompt updated.');
    }

    public function destroy(LeadSearchPreset $leadSearchPreset): RedirectResponse
    {
        $this->authorizePermission(Permissions::LEAD_SEARCH_PRESETS_DELETE);

        $leadSearchPreset->delete();

        return redirect()
            ->route('lead-search-presets.index')
            ->with('status', 'AI prompt deleted.');
    }

    /**
     * @return array{name: string, slug: string, criteria: string, is_active: bool, sort_order: int}
     */
    protected function payload(LeadSearchPresetRequest $request, ?LeadSearchPreset $existing = null): array
    {
        $name = $request->validated('name');
        $slug = Str::slug($request->validated('slug') ?: $name);

        if (blank($slug)) {
            $slug = 'prompt-'.Str::random(6);
        }

        $uniqueSlug = $slug;
        $suffix = 1;

        while (
            LeadSearchPreset::query()
                ->where('slug', $uniqueSlug)
                ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
                ->exists()
        ) {
            $uniqueSlug = $slug.'-'.$suffix;
            $suffix++;
        }

        return [
            'name' => $name,
            'slug' => $uniqueSlug,
            'criteria' => $request->validated('criteria'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($request->validated('sort_order') ?? 0),
        ];
    }
}
