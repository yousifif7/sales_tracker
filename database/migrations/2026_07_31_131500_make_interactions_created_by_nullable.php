<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite test DB: recreate column as nullable via schema builder if needed.
            // Fresh installs already get nullable from a rebuilt table in tests when
            // foreign keys are simulated differently — skip raw MySQL ALTER.
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE `interactions` DROP FOREIGN KEY `interactions_created_by_foreign`');
        DB::statement('ALTER TABLE `interactions` MODIFY `created_by` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `interactions` ADD CONSTRAINT `interactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        Schema::disableForeignKeyConstraints();

        DB::statement('ALTER TABLE `interactions` DROP FOREIGN KEY `interactions_created_by_foreign`');
        DB::statement('ALTER TABLE `interactions` MODIFY `created_by` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `interactions` ADD CONSTRAINT `interactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE');

        Schema::enableForeignKeyConstraints();
    }
};
