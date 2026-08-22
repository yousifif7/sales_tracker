<?php

namespace App\Console\Commands;

use App\Services\OutreachSequenceService;
use Illuminate\Console\Command;

class ProcessOutreachSequencesCommand extends Command
{
    protected $signature = 'outreach:process-sequences';

    protected $description = 'Send due sequence follow-ups/nudges and run day-15 exits (UK business days only)';

    public function handle(OutreachSequenceService $sequences): int
    {
        $stats = $sequences->processDue();

        $line = sprintf(
            'Sequences: processed=%d sent=%d exited=%d skipped=%d errors=%d',
            $stats['processed'],
            $stats['sent'],
            $stats['exited'],
            $stats['skipped'],
            $stats['errors'],
        );

        if (filled($stats['idle_reason'] ?? null)) {
            $line .= ' idle='.$stats['idle_reason'];
        }

        if (! empty($stats['exit_reasons'])) {
            $parts = [];
            foreach ($stats['exit_reasons'] as $reason => $count) {
                $parts[] = $reason.':'.$count;
            }
            $line .= ' exits=['.implode(',', $parts).']';
        }

        $this->info($line);

        return self::SUCCESS;
    }
}
