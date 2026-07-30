<?php

namespace App\Http\Controllers;

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Interaction;
use App\Models\Response;
use App\Support\Permissions;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorizePermission(Permissions::DASHBOARD_VIEW);

        $today = CarbonImmutable::today();
        $last30Days = $today->subDays(29)->startOfDay();

        $interactionCount = Interaction::query()
            ->whereBetween('sent_at', [$last30Days, $today->endOfDay()])
            ->count();

        $responseCount = Response::query()
            ->whereHas('interaction', fn ($query) => $query->whereBetween('sent_at', [$last30Days, $today->endOfDay()]))
            ->count();

        $pipeline = collect(ContactStatus::cases())
            ->mapWithKeys(fn (ContactStatus $status) => [
                $status->value => Contact::query()->where('status', $status)->count(),
            ]);

        return view('dashboard', [
            'responseRate' => $interactionCount > 0 ? round(($responseCount / $interactionCount) * 100, 1) : 0,
            'followUpsDueToday' => FollowUp::query()
                ->with('contact')
                ->whereDate('due_date', $today)
                ->where('completed', false)
                ->orderBy('due_date')
                ->get(),
            'followUpsDueThisWeek' => FollowUp::query()
                ->with('contact')
                ->whereBetween('due_date', [$today, $today->endOfWeek()])
                ->where('completed', false)
                ->orderBy('due_date')
                ->get(),
            'pipeline' => $pipeline,
        ]);
    }
}
