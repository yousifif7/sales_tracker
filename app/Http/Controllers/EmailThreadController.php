<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplyEmailThreadRequest;
use App\Models\EmailThread;
use App\Services\OutreachEmailService;
use App\Support\CurrentUserResolver;
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

        $threads = EmailThread::query()
            ->with(['contact', 'latestMessage'])
            ->withCount([
                'messages as outbound_sent_count' => fn ($q) => $q->where('direction', 'outbound')->where('delivery_status', 'sent'),
                'messages as opened_count' => fn ($q) => $q->where('direction', 'outbound')->where(fn ($inner) => $inner->whereNotNull('opened_at')->orWhere('open_count', '>', 0)),
                'messages as inbound_count' => fn ($q) => $q->where('direction', 'inbound'),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
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
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString();

        return view('email-threads.index', [
            'threads' => $threads,
            'trashedCount' => EmailThread::onlyTrashed()->count(),
        ]);
    }

    public function trash(Request $request): View
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

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
            ->paginate(20)
            ->withQueryString();

        return view('email-threads.trash', [
            'threads' => $threads,
        ]);
    }

    public function show(EmailThread $emailThread): View
    {
        $this->authorizePermission(Permissions::EMAILS_INBOX);

        $emailThread->load([
            'contact',
            'campaign',
            'messages' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
        ]);

        $replySubject = str_starts_with(strtolower($emailThread->subject), 're:')
            ? $emailThread->subject
            : 'Re: '.$emailThread->subject;

        return view('email-threads.show', [
            'thread' => $emailThread,
            'replySubject' => old('subject', $replySubject),
            'replyBody' => old('body', ''),
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
}
