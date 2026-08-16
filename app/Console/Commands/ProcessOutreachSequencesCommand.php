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

        $this->info(sprintf(
            'Sequences: processed=%d sent=%d exited=%d skipped=%d errors=%d',
            $stats['processed'],
            $stats['sent'],
            $stats['exited'],
            $stats['skipped'],
            $stats['errors'],
        ));

        return self::SUCCESS;
    }
}
