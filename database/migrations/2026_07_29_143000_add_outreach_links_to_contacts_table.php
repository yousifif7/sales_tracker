<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('website')->nullable()->after('source_url');
            $table->string('linkedin_url')->nullable()->after('website');
            $table->json('social_links')->nullable()->after('linkedin_url');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['website', 'linkedin_url', 'social_links']);
        });
    }
};
