<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HostingerStatusCommand extends Command
{
    protected $signature = 'app:hostinger-status';

    protected $description = 'Check Hostinger-ready queue, mail, IMAP, and schedule configuration';

    public function handle(): int
    {
        $this->info('Sales Tracker — Hostinger readiness check');
        $this->newLine();

        $checks = [
            'APP_ENV' => config('app.env'),
            'APP_URL' => config('app.url'),
            'QUEUE_CONNECTION' => config('queue.default'),
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_FROM' => config('mail.from.address'),
            'IMAP_HOST' => config('imap.host'),
            'IMAP_FOLDER' => config('imap.folder'),
            'IMAP_USER_SET' => filled(config('imap.username')) ? 'yes' : 'NO',
            'IMAP_PASS_SET' => filled(config('imap.password')) ? 'yes' : 'NO',
            'OPENROUTER_MODEL' => config('openrouter.model'),
            'OPENROUTER_KEY_SET' => filled(config('openrouter.api_key')) ? 'yes' : 'NO',
        ];

        foreach ($checks as $label => $value) {
            $this->line(sprintf('%-20s %s', $label, $value ?: '(empty)'));
        }

        $this->newLine();

        if (config('queue.default') !== 'database') {
            $this->warn('QUEUE_CONNECTION should be "database" on Hostinger shared hosting.');
        }

        if (! filled(config('imap.username')) || ! filled(config('imap.password'))) {
            $this->warn('IMAP credentials missing — inbox reply sync will not run.');
        }

        if (! Schema::hasTable('jobs')) {
            $this->error('Missing jobs table. Run: php artisan migrate');
        } else {
            $pending = DB::table('jobs')->count();
            $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
            $this->line("Pending jobs: {$pending}");
            $this->line("Failed jobs: {$failed}");
        }

        if (Schema::hasTable('email_threads')) {
            $this->line('Email threads: '.DB::table('email_threads')->count());
            $this->line('Email messages: '.DB::table('email_messages')->count());
        } else {
            $this->warn('email_threads table missing. Run: php artisan migrate');
        }

        $this->newLine();
        $this->info('Hostinger cron (every minute):');
        $this->line('* * * * * cd /home/USER/domains/YOURDOMAIN/public_html && /usr/bin/php artisan schedule:run >> /dev/null 2>&1');
        $this->newLine();
        $this->comment('Replace USER/YOURDOMAIN/php path with values from Hostinger panel.');
        $this->comment('Manual IMAP test: php artisan email:sync-imap');

        return self::SUCCESS;
    }
}
