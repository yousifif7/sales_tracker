<x-layouts.app title="{{ $thread->subject }} | Inbox" heading="{{ $thread->subject }}" eyebrow="Email thread">
    <section class="panel mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm text-slate-400">Conversation with</p>
                <p class="text-lg font-semibold text-white">
                    <a class="hover:text-sky-300" href="{{ route('contacts.show', $thread->contact) }}">{{ $thread->contact?->name }}</a>
                    <span class="text-slate-400 text-base font-normal">&lt;{{ $thread->contact?->email }}&gt;</span>
                </p>
                <p class="mt-1 text-sm text-slate-500">{{ $thread->contact?->company }}</p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-800 px-3 py-1 font-semibold text-slate-300">{{ $thread->status->label() }}</span>
                    @if ($thread->hasOutboundSent())
                        <span class="rounded-full bg-emerald-500/15 px-3 py-1 font-semibold text-emerald-200">Sent</span>
                    @endif
                    @if ($thread->hasBeenOpened())
                        <span class="rounded-full bg-sky-500/15 px-3 py-1 font-semibold text-sky-200">Opened</span>
                    @endif
                    @if ($thread->hasInboundReply())
                        <span class="rounded-full bg-amber-500/15 px-3 py-1 font-semibold text-amber-200">Responded</span>
                    @endif
                    @if ($thread->has_unread)
                        <span class="rounded-full bg-sky-500/20 px-3 py-1 font-semibold text-sky-100">New reply</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a class="btn-secondary" href="{{ route('email-threads.index') }}">Back to inbox</a>
                @can(\App\Support\Permissions::EMAILS_SEND)
                    <a class="btn-secondary" href="{{ route('contacts.email.create', $thread->contact) }}">New email</a>
                @endcan
                <form method="post" action="{{ route('email-threads.destroy', $thread) }}" onsubmit="return confirm('Move this thread to trash?')">
                    @csrf
                    @method('delete')
                    <button class="btn-secondary" type="submit">Move to trash</button>
                </form>
            </div>
        </div>
    </section>

    <section class="panel mb-6 space-y-4">
        <h3 class="text-lg font-semibold text-white">Messages</h3>

        @forelse ($thread->messages as $message)
            <article @class([
                'rounded-2xl border p-4',
                'border-sky-500/30 bg-sky-500/5' => $message->direction->value === 'outbound',
                'border-slate-700 bg-slate-950/70' => $message->direction->value === 'inbound',
            ])>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-medium text-white">
                            {{ $message->direction->label() }}
                            <span class="text-sm font-normal text-slate-400">
                                {{ $message->direction->value === 'outbound' ? 'to' : 'from' }}
                                {{ $message->direction->value === 'outbound' ? $message->to_email : $message->from_email }}
                            </span>
                        </p>
                        <p class="text-sm text-slate-400">{{ $message->subject }}</p>
                    </div>
                    <div class="text-right text-xs text-slate-500">
                        <p>{{ optional($message->sent_at ?? $message->received_at ?? $message->created_at)->format('M d, Y H:i') }}</p>
                        @if ($message->direction->value === 'outbound')
                            <p class="mt-1">
                                @if ($message->delivery_status?->value === 'sent')
                                    <span class="text-emerald-300">Sent</span>
                                @elseif ($message->delivery_status?->value === 'failed')
                                    <span class="text-rose-300">Failed</span>
                                @endif
                                @if ($message->open_count > 0)
                                    · <span class="text-sky-300">Opened {{ $message->open_count }}×</span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                <div class="prose-email mt-4 text-sm text-slate-200">
                    @php
                        $htmlVisible = filled($message->body_html) && trim(strip_tags((string) $message->body_html)) !== '';
                        $textVisible = filled($message->body_text);
                    @endphp
                    @if ($htmlVisible)
                        {!! \App\Support\HtmlContent::sanitizeInbound($message->body_html) !!}
                    @elseif ($textVisible)
                        <pre class="whitespace-pre-wrap font-sans text-slate-300">{{ $message->body_text }}</pre>
                    @else
                        <p class="text-slate-500 italic">No message body was captured for this email. It will be backfilled on the next inbox sync if still available on the mail server.</p>
                    @endif
                </div>
            </article>
        @empty
            <p class="text-sm text-slate-500">No messages in this thread yet.</p>
        @endforelse
    </section>

    @can(\App\Support\Permissions::EMAILS_SEND)
        <section class="panel">
            <h3 class="mb-4 text-lg font-semibold text-white">Reply</h3>
            <form method="post" action="{{ route('email-threads.reply', $thread) }}" class="space-y-5" data-rich-form>
                @csrf
                <div>
                    <label class="label" for="subject">Subject</label>
                    <input class="input" id="subject" name="subject" value="{{ $replySubject }}" required>
                </div>
                <x-rich-editor name="body" :value="$replyBody" hint="Your reply stays in this thread." />
                <button class="btn-primary" type="submit">Send reply</button>
            </form>
        </section>
    @endcan
</x-layouts.app>
