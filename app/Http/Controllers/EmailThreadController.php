<?php

namespace App\Http\Controllers;

use App\Enums\EmailThreadStatus;
use App\Http\Requests\ReplyEmailThreadRequest;
use App\Models\EmailThread;
use App\Services\OutreachEmailService;
use App\Support\CurrentUserResolver;
use App\Support\OutreachTemplateRenderer;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EmailThreadController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $perPage = $this->resolvePerPage($request);
        $dateFrom = $this->resolveDateFilter($request->input('date_from'));
        $dateTo = $this->resolveDateFilter($request->input('date_to'));

        $threads = EmailThread::query()
            ->with(['contact', 'latestMessage'])
            ->withCount([
                'messages as outbound_sent_count' => fn ($q) => $q->where('direction', 'outbound')->where('delivery_status', 'sent'),
                'messages as opened_count' => fn ($q) => $q->where('direction', 'outbound')->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                'messages as inbound_count' => fn ($q) => $q->where('direction', 'inbound'),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('unread'), fn ($q) => $q->where('has_unread', true))
            ->when($request->boolean('opened'), function ($q): void {
                $q->whereHas(
                    'messages',
                    fn ($messageQuery) => $messageQuery
                        ->where('direction', 'outbound')
                        ->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                );
            })
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($nested) use ($search): void {
                    $nested->where('subject', 'like', $search)
                        ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                            $contactQuery->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search)
                                ->orWhere('company', 'like', $search);
                        });
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('last_message_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('last_message_at', '<=', $dateTo))
            ->latest('last_message_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('email-threads.index', [
            'threads' => $threads,
            'trashedCount' => EmailThread::onlyTrashed()->count(),
            'stats' => $this->inboxStats(),
            'perPage' => $perPage,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function trash(Request $request): View
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $perPage = $this->resolvePerPage($request);

        $threads = EmailThread::onlyTrashed()
            ->with(['contact', 'latestMessage'])
            ->withCount([
                'messages as outbound_sent_count' => fn ($q) => $q->where('direction', 'outbound')->where('delivery_status', 'sent'),
                'messages as opened_count' => fn ($q) => $q->where('direction', 'outbound')->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                'messages as inbound_count' => fn ($q) => $q->where('direction', 'inbound'),
            ])
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $q->where(function ($nested) use ($search): void {
                    $nested->where('subject', 'like', $search)
                        ->orWhereHas('contact', function ($contactQuery) use ($search): void {
                            $contactQuery->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search)
                                ->orWhere('company', 'like', $search);
                        });
                });
            })
            ->latest('deleted_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('email-threads.trash', [
            'threads' => $threads,
            'perPage' => $perPage,
        ]);
    }

    public function show(EmailThread $emailThread, OutreachTemplateRenderer $templates): View
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        if ($emailThread->has_unread) {
            $emailThread->update(['has_unread' => false]);
        }

        $emailThread->load([
            'contact',
            'campaign',
            'messages' => fn ($q) => $q->orderByRaw('COALESCE(sent_at, received_at, created_at)')->orderBy('id'),
        ]);

        $replySubject = str_starts_with(strtolower($emailThread->subject), 're:')
            ? $emailThread->subject
            : 'Re: '.$emailThread->subject;

        $templateKey = request('template');
        $replyBody = old('body', '');

        if (filled($templateKey) && $emailThread->contact && ! old('body')) {
            $rendered = $templates->render((string) $templateKey, $emailThread->contact);
            $replyBody = $rendered['body'];
        }

        return view('email-threads.show', [
            'thread' => $emailThread,
            'templates' => $templates->options(),
            'selectedTemplate' => $templateKey,
            'replySubject' => old('subject', $replySubject),
            'replyBody' => $replyBody,
        ]);
    }

    public function reply(
        ReplyEmailThreadRequest $request,
        EmailThread $emailThread,
        CurrentUserResolver $currentUserResolver,
        OutreachEmailService $outreachEmailService,
    ): RedirectResponse {
        $this->authorizePermission(Permissions::EMAILS_SEND);

        $emailThread->load('contact');
        abort_unless(filled($emailThread->contact?->email), 422, 'This contact has no email address.');

        $replyTo = $emailThread->messages()
            ->where('direction', 'inbound')
            ->latest('id')
            ->first()
            ?? $emailThread->messages()->where('direction', 'outbound')->latest('id')->first();

        try {
            $outreachEmailService->send(
                contact: $emailThread->contact,
                subject: $request->validated('subject'),
                bodyHtml: $request->validated('body'),
                campaignId: $emailThread->campaign_id,
                userId: $currentUserResolver->id(),
                thread: $emailThread,
                replyTo: $replyTo,
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['email' => 'SMTP send failed: '.$exception->getMessage()]);
        }

        return redirect()
            ->route('email-threads.show', $emailThread)
            ->with('status', 'Reply sent.');
    }

    public function destroy(EmailThread $emailThread): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $emailThread->delete();

        return redirect()
            ->route('email-threads.index')
            ->with('status', 'Thread moved to trash.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $ids = $validated['ids'];

        $count = EmailThread::query()->whereIn('id', $ids)->count();

        EmailThread::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('email-threads.index')
            ->with('status', $count === 1
                ? '1 thread moved to trash.'
                : "{$count} threads moved to trash.");
    }

    public function restore(int $emailThread): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $thread = EmailThread::onlyTrashed()->findOrFail($emailThread);
        $thread->restore();

        return redirect()
            ->route('email-threads.trash')
            ->with('status', 'Thread restored.');
    }

    public function forceDestroy(int $emailThread): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $thread = EmailThread::onlyTrashed()->findOrFail($emailThread);
        $thread->forceDelete();

        return redirect()
            ->route('email-threads.trash')
            ->with('status', 'Thread permanently deleted.');
    }

    public function bulkForceDestroy(Request $request): RedirectResponse
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ]);

        $threads = EmailThread::onlyTrashed()
            ->whereIn('id', $validated['ids'])
            ->get();

        $count = $threads->count();

        foreach ($threads as $thread) {
            $thread->forceDelete();
        }

        return redirect()
            ->route('email-threads.trash')
            ->with('status', $count === 1
                ? '1 thread permanently deleted.'
                : "{$count} threads permanently deleted.");
    }

    /**
     * @return array{total: int, unread: int, awaiting_reply: int, responded: int, opened: int}
     */
    protected function inboxStats(): array
    {
        return [
            'total' => EmailThread::query()->count(),
            'unread' => EmailThread::query()->where('has_unread', true)->count(),
            'awaiting_reply' => EmailThread::query()
                ->where('status', EmailThreadStatus::AwaitingReply->value)
                ->count(),
            'responded' => EmailThread::query()
                ->where('status', EmailThreadStatus::Responded->value)
                ->count(),
            'opened' => EmailThread::query()
                ->whereHas(
                    'messages',
                    fn ($q) => $q->where('direction', 'outbound')
                        ->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                )
                ->count(),
        ];
    }

    protected function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 20);

        return in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;
    }

    protected function resolveDateFilter(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
