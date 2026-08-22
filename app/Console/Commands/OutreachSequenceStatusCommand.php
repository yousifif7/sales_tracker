<?php

namespace App\Console\Commands;

use App\Enums\EmailSequenceStatus;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailTemplate;
use App\Support\BusinessDays;
use Illuminate\Console\Command;

class OutreachSequenceStatusCommand extends Command
{
    protected $signature = 'outreach:sequence-status';

    protected $description = 'Show active outreach sequence enrollments and whether they are due';

    public function handle(): int
    {
        $tz = (string) config('outreach.sequence.timezone', 'Europe/London');
        $now = now()->timezone($tz);

        $this->info('Outreach sequence status');
        $this->line('Now ('.$tz.'): '.$now->toDateTimeString().' — '.(BusinessDays::isBusinessDay() ? 'business day' : 'weekend/holiday skip'));
        $this->newLine();

        foreach (['fieldline_followup', 'fieldline_final_nudge'] as $slug) {
            $db = EmailTemplate::query()->where('slug', $slug)->first();
            $config = config('outreach.templates.'.$slug);
            $this->line(sprintf(
                'Template %-22s db=%s active=%s config=%s',
                $slug,
                $db ? 'yes' : 'NO',
                $db?->is_active ? 'yes' : 'no',
                is_array($config) ? 'yes' : 'NO',
            ));
        }

        $this->newLine();

        $active = EmailSequenceEnrollment::query()
            ->active()
            ->with('contact')
            ->orderBy('next_action_at')
            ->get();

        $this->line('Active enrollments: '.$active->count());
        $this->line('Completed (all time): '.EmailSequenceEnrollment::query()->where('status', EmailSequenceStatus::Completed)->count());

        if ($active->isEmpty()) {
            $this->warn('No active enrollments — cold sends must check “Enroll in automated sequence”.');

            return self::SUCCESS;
        }

        $rows = $active->map(function (EmailSequenceEnrollment $enrollment) {
            $due = $enrollment->next_action_at && $enrollment->next_action_at->lte(now());

            return [
                $enrollment->id,
                $enrollment->contact?->name ?: '—',
                $enrollment->next_step?->value ?: '—',
                $enrollment->next_action_at?->toDateTimeString() ?: '—',
                $due ? 'DUE' : 'waiting',
                $enrollment->followup_sent_at ? 'yes' : 'no',
                $enrollment->nudge_sent_at ? 'yes' : 'no',
            ];
        })->all();

        $this->table(
            ['ID', 'Contact', 'Next', 'next_action_at', 'Due?', 'FU sent', 'Nudge sent'],
            $rows,
        );

        $dueCount = $active->filter(fn (EmailSequenceEnrollment $e) => $e->next_action_at && $e->next_action_at->lte(now()))->count();
        $this->newLine();
        $this->info("Due right now: {$dueCount}");

        return self::SUCCESS;
    }
}
