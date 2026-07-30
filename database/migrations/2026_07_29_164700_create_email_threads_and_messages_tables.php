<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->string('status')->default('awaiting_reply');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'last_message_at']);
            $table->index('status');
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('direction'); // outbound | inbound
            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->string('message_id')->nullable()->unique();
            $table->string('in_reply_to')->nullable();
            $table->text('references')->nullable();
            $table->string('imap_uid')->nullable();
            $table->string('imap_folder')->nullable();
            $table->string('tracking_token', 64)->nullable()->unique();
            $table->string('delivery_status')->nullable(); // sent | failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();

            $table->index(['imap_folder', 'imap_uid']);
            $table->index(['email_thread_id', 'created_at']);
            $table->index('in_reply_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_threads');
    }
};
