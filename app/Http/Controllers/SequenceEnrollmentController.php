<?php

namespace App\Http\Controllers;

use App\Enums\EmailSequenceExitReason;
use App\Models\EmailSequenceEnrollment;
use App\Services\OutreachSequenceService;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class SequenceEnrollmentController extends Controller
{
    public function markStep(
        EmailSequenceEnrollment $enrollment,
        OutreachSequenceService $sequences,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        try {
            $sequences->markStepComplete($enrollment);
        } catch (\InvalidArgumentException $exception) {
            return redirect()
                ->back()
                ->withErrors(['sequence' => $exception->getMessage()]);
        }

        $enrollment->refresh();

        $nextLabel = $enrollment->next_step?->label() ?? 'next step';
        $scheduled = $enrollment->next_action_at
            ? $enrollment->next_action_at->timezone(config('outreach.sequence.timezone', 'Europe/London'))->format('D M j, Y g:ia').' UK'
            : 'soon';

        return redirect()
            ->back()
            ->with('status', "Step marked as sent manually. Next: {$nextLabel} on {$scheduled}.");
    }

    public function sendNow(
        EmailSequenceEnrollment $enrollment,
        OutreachSequenceService $sequences,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        try {
            $result = $sequences->sendNow($enrollment);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withErrors(['sequence' => 'Send failed: '.$exception->getMessage()]);
        }

        return $this->redirectWithOutcome($result, 'Sent successfully.');
    }

    public function retry(
        EmailSequenceEnrollment $enrollment,
        OutreachSequenceService $sequences,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        try {
            $result = $sequences->retry($enrollment);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withErrors(['sequence' => 'Retry failed: '.$exception->getMessage()]);
        }

        if (($result['outcome'] ?? '') === 'skipped') {
            return redirect()
                ->back()
                ->withErrors(['sequence' => $result['message'] ?? 'Could not retry this enrollment.']);
        }

        return $this->redirectWithOutcome($result, 'Sequence reactivated and processed.');
    }

    public function bulkCancel(Request $request, OutreachSequenceService $sequences): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $ids = $this->validatedEnrollmentIds($request);
        $stats = $sequences->bulkCancel($ids);

        return redirect()
            ->back()
            ->with('status', $this->bulkMessage('cancelled', $stats['cancelled'], $stats['skipped']));
    }

    public function bulkSendNow(Request $request, OutreachSequenceService $sequences): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $ids = $this->validatedEnrollmentIds($request);
        $stats = $sequences->bulkSendNow($ids);

        return redirect()
            ->back()
            ->with('status', $this->bulkSendMessage($stats));
    }

    public function bulkRetry(Request $request, OutreachSequenceService $sequences): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $ids = $this->validatedEnrollmentIds($request);
        $stats = $sequences->bulkRetry($ids);

        return redirect()
            ->back()
            ->with('status', $this->bulkRetryMessage($stats));
    }

    public function bulkMarkStep(Request $request, OutreachSequenceService $sequences): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $ids = $this->validatedEnrollmentIds($request);
        $stats = $sequences->bulkMarkStepComplete($ids);

        return redirect()
            ->back()
            ->with('status', $this->bulkMessage('marked as sent', $stats['marked'], $stats['skipped']));
    }

    /**
     * @return list<int>
     */
    protected function validatedEnrollmentIds(Request $request): array
    {
        $validated = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1', 'max:50'],
            'enrollment_ids.*' => ['integer', 'distinct', 'exists:email_sequence_enrollments,id'],
        ]);

        return $validated['enrollment_ids'];
    }

    /**
     * @param  array{sent: int, exited: int, skipped: int, errors: int}  $stats
     */
    protected function bulkSendMessage(array $stats): string
    {
        $parts = array_filter([
            $stats['sent'] > 0 ? "{$stats['sent']} sent" : null,
            $stats['exited'] > 0 ? "{$stats['exited']} finished" : null,
            $stats['skipped'] > 0 ? "{$stats['skipped']} skipped" : null,
            $stats['errors'] > 0 ? "{$stats['errors']} failed" : null,
        ]);

        return $parts !== [] ? 'Bulk send: '.implode(', ', $parts).'.' : 'No enrollments were processed.';
    }

    /**
     * @param  array{retried: int, sent: int, exited: int, skipped: int, errors: int}  $stats
     */
    protected function bulkRetryMessage(array $stats): string
    {
        $parts = array_filter([
            $stats['retried'] > 0 ? "{$stats['retried']} retried" : null,
            $stats['sent'] > 0 ? "{$stats['sent']} sent" : null,
            $stats['exited'] > 0 ? "{$stats['exited']} finished" : null,
            $stats['skipped'] > 0 ? "{$stats['skipped']} skipped" : null,
            $stats['errors'] > 0 ? "{$stats['errors']} failed" : null,
        ]);

        return $parts !== [] ? 'Bulk retry: '.implode(', ', $parts).'.' : 'No enrollments were retried.';
    }

    protected function bulkMessage(string $verb, int $done, int $skipped): string
    {
        $message = $done === 1 ? "1 enrollment {$verb}." : "{$done} enrollments {$verb}.";

        if ($skipped > 0) {
            $message .= " {$skipped} skipped.";
        }

        return $message;
    }

    /**
     * @param  array{outcome: string, exit_reason?: string, message?: string}  $result
     */
    protected function redirectWithOutcome(array $result, string $sentMessage): RedirectResponse
    {
        return match ($result['outcome'] ?? 'skipped') {
            'sent' => redirect()->back()->with('status', $sentMessage),
            'exited' => redirect()->back()->with(
                'status',
                'Sequence finished: '.$this->exitReasonLabel($result['exit_reason'] ?? null).'.',
            ),
            default => redirect()->back()->withErrors([
                'sequence' => $result['message'] ?? 'Nothing was sent for this enrollment.',
            ]),
        };
    }

    protected function exitReasonLabel(?string $reason): string
    {
        if ($reason === null) {
            return 'Completed';
        }

        $enum = EmailSequenceExitReason::tryFrom($reason);

        return $enum?->label() ?? str($reason)->replace('_', ' ')->title()->toString();
    }
}
