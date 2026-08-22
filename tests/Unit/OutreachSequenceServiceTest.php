<?php

namespace Tests\Unit;

use App\Enums\ContactStatus;
use App\Enums\EmailDeliveryStatus;
use App\Enums\EmailMessageDirection;
use App\Enums\EmailSequenceExitReason;
use App\Enums\EmailSequenceNextStep;
use App\Enums\EmailSequenceStatus;
use App\Enums\EmailThreadStatus;
use App\Models\Contact;
use App\Models\EmailMessage;
use App\Models\EmailSequenceEnrollment;
use App\Models\EmailTemplate;
use App\Models\EmailThread;
use App\Models\FollowUp;
use App\Services\OutreachSequenceService;
use App\Support\BusinessDays;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutreachSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'outreach.sequence.timezone' => 'Europe/London',
            'outreach.sequence.send_hour' => 9,
            'outreach.sequence.followup_business_days' => 4,
            'outreach.sequence.nudge_business_days' => 8,
            'outreach.sequence.exit_business_days' => 15,
            'outreach.sequence.followup_template' => 'fieldline_followup',
            'outreach.sequence.nudge_template' => 'fieldline_final_nudge',
            'outreach.sequence.hot_open_min_total_opens' => 5,
            'outreach.sequence.hot_open_min_unique_emails' => 2,
        ]);
    }

    public function test_enroll_schedules_followup_on_business_day_four(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-14 11:00:00', 'Europe/London'));

        [$contact, $thread, $message] = $this->makeColdSend();

        $enrollment = app(OutreachSequenceService::class)->enroll($contact, $thread, $message);

        $this->assertNotNull($enrollment);
        $this->assertSame(EmailSequenceStatus::Active, $enrollment->status);
        $this->assertSame(EmailSequenceNextStep::Followup, $enrollment->next_step);
        $this->assertSame(
            BusinessDays::addAfter($message->sent_at, 4)->toDateTimeString(),
            $enrollment->next_action_at->toDateTimeString(),
        );
        $this->assertSame('Own vs rent — control room for Acme', $enrollment->cold_subject);
    }

    public function test_does_not_double_enroll_active_sequence(): void
    {
        [$contact, $thread, $message] = $this->makeColdSend();
        $service = app(OutreachSequenceService::class);

        $this->assertNotNull($service->enroll($contact, $thread, $message));
        $this->assertNull($service->enroll($contact, $thread, $message));
        $this->assertSame(1, EmailSequenceEnrollment::query()->count());
    }

    public function test_complete_for_contact_stops_sequence(): void
    {
        [$contact, $thread, $message] = $this->makeColdSend();
        $service = app(OutreachSequenceService::class);
        $enrollment = $service->enroll($contact, $thread, $message);

        $service->completeForContact($contact, EmailSequenceExitReason::Replied);

        $enrollment->refresh();
        $this->assertSame(EmailSequenceStatus::Completed, $enrollment->status);
        $this->assertSame(EmailSequenceExitReason::Replied, $enrollment->exit_reason);
        $this->assertNull($enrollment->next_action_at);
    }

    public function test_quiet_exit_marks_contact_lost(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-05 10:00:00', 'Europe/London')); // Friday

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        $enrollment = EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Exit,
            'next_action_at' => now()->subMinute(),
            'enrolled_at' => $message->sent_at,
            'cold_subject' => 'Own vs rent — control room for Acme',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        $stats = app(OutreachSequenceService::class)->processDue();

        $enrollment->refresh();
        $contact->refresh();

        $this->assertSame(1, $stats['exited']);
        $this->assertSame(EmailSequenceStatus::Completed, $enrollment->status);
        $this->assertSame(EmailSequenceExitReason::QuietLost, $enrollment->exit_reason);
        $this->assertSame(ContactStatus::Lost, $contact->status);
    }

    public function test_hot_open_exit_creates_follow_up_and_keeps_contacted(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-09-05 10:00:00', 'Europe/London'));

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        $message->update([
            'opened_at' => now()->subDays(2),
            'open_count' => 6,
        ]);

        $enrollment = EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Exit,
            'next_action_at' => now()->subMinute(),
            'enrolled_at' => $message->sent_at,
            'cold_subject' => 'Own vs rent — control room for Acme',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        app(OutreachSequenceService::class)->processDue();

        $enrollment->refresh();
        $contact->refresh();

        $this->assertSame(EmailSequenceExitReason::HotOpens, $enrollment->exit_reason);
        $this->assertSame(ContactStatus::Contacted, $contact->status);
        $this->assertTrue(
            FollowUp::query()
                ->where('contact_id', $contact->id)
                ->where('completed', false)
                ->where('note', 'like', '%hot opens%')
                ->exists()
        );
    }

    public function test_process_due_skips_weekends(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-16 10:00:00', 'Europe/London')); // Saturday

        [$contact, $thread, $message] = $this->makeColdSend();

        EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Exit,
            'next_action_at' => now()->subMinute(),
            'enrolled_at' => $message->sent_at,
            'cold_subject' => 'Own vs rent',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        $stats = app(OutreachSequenceService::class)->processDue();

        $this->assertSame(0, $stats['processed']);
        $this->assertSame('weekend', $stats['idle_reason']);
        $this->assertSame(1, EmailSequenceEnrollment::query()->active()->count());
    }

    public function test_unrelated_inbound_on_another_thread_does_not_stop_sequence(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-20 10:00:00', 'Europe/London')); // Wednesday

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        $otherThread = EmailThread::query()->create([
            'contact_id' => $contact->id,
            'subject' => 'Unrelated newsletter',
            'status' => EmailThreadStatus::Responded,
            'last_message_at' => now(),
        ]);

        EmailMessage::query()->create([
            'email_thread_id' => $otherThread->id,
            'direction' => EmailMessageDirection::Inbound,
            'from_email' => $contact->email,
            'to_email' => 'yousif@example.com',
            'subject' => 'Unrelated',
            'body_html' => '<p>hi</p>',
            'body_text' => 'hi',
            'message_id' => '<other@example.com>',
            'tracking_token' => null,
            'delivery_status' => null,
            'received_at' => now(),
            'open_count' => 0,
        ]);

        EmailTemplate::query()->create([
            'name' => 'FieldLine follow-up',
            'slug' => 'fieldline_followup',
            'subject' => 'Re: Own vs rent — control room for {{company}}',
            'body' => '<p>Quick bump</p>',
            'is_active' => true,
        ]);

        EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Followup,
            'next_action_at' => now()->subMinute(),
            'enrolled_at' => $message->sent_at,
            'cold_subject' => 'Own vs rent — control room for Acme',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        $stats = app(OutreachSequenceService::class)->processDue();

        $this->assertSame(1, $stats['sent']);
        $this->assertSame(0, $stats['exited']);
        $this->assertSame(1, EmailSequenceEnrollment::query()->active()->count());
    }

    public function test_process_due_falls_back_to_config_template_when_db_row_missing(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-20 10:00:00', 'Europe/London'));

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Active,
            'next_step' => EmailSequenceNextStep::Followup,
            'next_action_at' => now()->subMinute(),
            'enrolled_at' => $message->sent_at,
            'cold_subject' => 'Own vs rent — control room for Acme',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        $this->assertSame(0, EmailTemplate::query()->where('slug', 'fieldline_followup')->count());

        $stats = app(OutreachSequenceService::class)->processDue();

        $this->assertSame(1, $stats['sent']);
        $this->assertSame(0, $stats['exited']);
    }

    public function test_mark_step_complete_advances_followup_to_nudge(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-20 10:00:00', 'Europe/London'));

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        $enrollment = app(OutreachSequenceService::class)->enroll($contact, $thread, $message);
        app(OutreachSequenceService::class)->markStepComplete($enrollment);

        $enrollment->refresh();

        $this->assertNotNull($enrollment->followup_sent_at);
        $this->assertSame(EmailSequenceNextStep::Nudge, $enrollment->next_step);
        $this->assertSame(
            BusinessDays::addAfter($message->sent_at, 8)->toDateTimeString(),
            $enrollment->next_action_at->toDateTimeString(),
        );
    }

    public function test_reactivate_and_retry_after_missing_template(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-08-20 10:00:00', 'Europe/London'));

        [$contact, $thread, $message] = $this->makeColdSend(
            enrolledAt: Carbon::parse('2025-08-14 11:00:00', 'Europe/London'),
        );

        $enrollment = EmailSequenceEnrollment::query()->create([
            'contact_id' => $contact->id,
            'email_thread_id' => $thread->id,
            'cold_message_id' => $message->id,
            'status' => EmailSequenceStatus::Completed,
            'next_step' => EmailSequenceNextStep::Followup,
            'next_action_at' => null,
            'enrolled_at' => $message->sent_at,
            'completed_at' => now(),
            'exit_reason' => EmailSequenceExitReason::MissingTemplate,
            'cold_subject' => 'Own vs rent — control room for Acme',
            'followup_template_slug' => 'fieldline_followup',
            'nudge_template_slug' => 'fieldline_final_nudge',
        ]);

        EmailTemplate::query()->create([
            'name' => 'FieldLine follow-up',
            'slug' => 'fieldline_followup',
            'subject' => 'Re: Own vs rent — control room for {{company}}',
            'body' => '<p>Quick bump</p>',
            'is_active' => true,
        ]);

        $service = app(OutreachSequenceService::class);
        $this->assertTrue($service->canReactivate($enrollment));

        $result = $service->retry($enrollment);

        $this->assertSame('sent', $result['outcome']);

        $enrollment->refresh();
        $this->assertSame(EmailSequenceStatus::Active, $enrollment->status);
        $this->assertNull($enrollment->exit_reason);
        $this->assertNotNull($enrollment->followup_sent_at);
        $this->assertSame(EmailSequenceNextStep::Nudge, $enrollment->next_step);
    }

    public function test_bulk_cancel_stops_active_enrollments(): void
    {
        [$contact, $thread, $message] = $this->makeColdSend();
        $enrollment = app(OutreachSequenceService::class)->enroll($contact, $thread, $message);

        $stats = app(OutreachSequenceService::class)->bulkCancel([$enrollment->id]);

        $enrollment->refresh();
        $this->assertSame(1, $stats['cancelled']);
        $this->assertSame(EmailSequenceStatus::Completed, $enrollment->status);
        $this->assertSame(EmailSequenceExitReason::Cancelled, $enrollment->exit_reason);
    }

    /**
     * @return array{0: Contact, 1: EmailThread, 2: EmailMessage}
     */
    protected function makeColdSend(?Carbon $enrolledAt = null): array
    {
        $sentAt = $enrolledAt ?? now();

        $contact = Contact::query()->create([
            'name' => 'Ada Lovelace',
            'company' => 'Acme',
            'email' => 'ada@example.com',
            'status' => ContactStatus::Contacted,
            'source' => 'manual',
        ]);

        $thread = EmailThread::query()->create([
            'contact_id' => $contact->id,
            'subject' => 'Own vs rent — control room for Acme',
            'status' => EmailThreadStatus::AwaitingReply,
            'last_message_at' => $sentAt,
        ]);

        $message = EmailMessage::query()->create([
            'email_thread_id' => $thread->id,
            'direction' => EmailMessageDirection::Outbound,
            'from_email' => 'yousif@example.com',
            'to_email' => $contact->email,
            'subject' => 'Own vs rent — control room for Acme',
            'body_html' => '<p>Hello</p>',
            'body_text' => 'Hello',
            'message_id' => '<cold@example.com>',
            'tracking_token' => 'token123',
            'delivery_status' => EmailDeliveryStatus::Sent,
            'sent_at' => $sentAt,
            'open_count' => 0,
        ]);

        return [$contact, $thread, $message];
    }
}
