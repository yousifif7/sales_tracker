<?php

namespace App\Console\Commands;

use App\Services\ImapInboxSyncService;
use Illuminate\Console\Command;

class SyncImapInboxCommand extends Command
{
    protected $signature = 'email:sync-imap';

    protected $description = 'Fetch inbound email replies via IMAP and attach them to CRM threads';

    public function handle(ImapInboxSyncService $syncService): int
    {
        $result = $syncService->sync();

        $this->info("Imported: {$result['imported']}");
        $this->info("Skipped: {$result['skipped']}");

        foreach ($result['errors'] as $error) {
            $this->error($error);
        }

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
