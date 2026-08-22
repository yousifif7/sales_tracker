<?php

namespace App\Http\Controllers;

use App\Enums\EmailSequenceStatus;
use App\Models\EmailSequenceEnrollment;
use App\Support\BusinessDays;
use App\Support\Permissions;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutreachSequenceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $tz = (string) config('outreach.sequence.timezone', 'Europe/London');
        $now = now()->timezone($tz);
        $todayStart = CarbonImmutable::parse($now)->startOfDay();
        $todayEnd = $todayStart->endOfDay();
        $upcomingEnd = $todayStart->addDays(14)->endOfDay();

        $scope = $request->string('scope')->toString() ?: 'today';
        if (! in_array($scope, ['today', 'upcoming', 'active', 'recent'], true)) {
            $scope = 'today';
        }

        $enrollments = EmailSequenceEnrollment::query()
            ->with(['contact', 'thread', 'campaign'])
            ->when(
                $scope === 'recent',
                fn ($query) => $query
                    ->where('status', EmailSequenceStatus::Completed)
                    ->where('completed_at', '>=', now()->subDays(30))
                    ->orderByDesc('completed_at'),
                fn ($query) => $query
                    ->active()
                    ->when($scope === 'today', fn ($q) => $q->where('next_action_at', '<=', $todayEnd))
                    ->when(
                        $scope === 'upcoming',
                        fn ($q) => $q
                            ->where('next_action_at', '>', $todayEnd)
                            ->where('next_action_at', '<=', $upcomingEnd),
                    )
                    ->orderBy('next_action_at'),
            )
            ->paginate(20)
            ->withQueryString();

        $activeBase = EmailSequenceEnrollment::query()->active();

        return view('outreach-sequences.index', [
            'enrollments' => $enrollments,
            'scope' => $scope,
            'stats' => [
                'active' => (clone $activeBase)->count(),
                'due_now' => (clone $activeBase)->where('next_action_at', '<=', now())->count(),
                'due_today' => (clone $activeBase)->where('next_action_at', '<=', $todayEnd)->count(),
                'upcoming' => (clone $activeBase)
                    ->where('next_action_at', '>', $todayEnd)
                    ->where('next_action_at', '<=', $upcomingEnd)
                    ->count(),
            ],
            'timezone' => $tz,
            'now' => $now,
            'isBusinessDay' => BusinessDays::isBusinessDay(),
            'sequenceConfig' => config('outreach.sequence'),
        ]);
    }
}
