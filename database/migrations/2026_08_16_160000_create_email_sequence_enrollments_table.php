<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cold_message_id')->nullable()->constrained('email_messages')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('active')->index();
            $table->string('next_step', 32)->default('followup')->index();
            $table->dateTime('next_action_at')->nullable()->index();
            $table->dateTime('enrolled_at')->index();
            $table->dateTime('followup_sent_at')->nullable();
            $table->dateTime('nudge_sent_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('exit_reason', 64)->nullable();
            $table->string('cold_subject');
            $table->string('followup_template_slug');
            $table->string('nudge_template_slug');
            $table->timestamps();

            $table->index(['status', 'next_action_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequence_enrollments');
    }
};
