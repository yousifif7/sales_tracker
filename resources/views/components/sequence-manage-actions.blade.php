@props([
    'enrollment',
    'contact' => null,
    'variant' => 'panel',
])

@php
    use App\Enums\EmailSequenceExitReason;
    use App\Support\Permissions;

    $contact ??= $enrollment->contact;
    $canRetry = $enrollment->status === \App\Enums\EmailSequenceStatus::Completed
        && in_array($enrollment->exit_reason, [
            EmailSequenceExitReason::MissingTemplate,
            EmailSequenceExitReason::SendFailed,
        ], true);

    $isTable = $variant === 'table';
@endphp

@can(Permissions::EMAILS_SEND)
    <div {{ $attributes->merge(['class' => $isTable ? 'flex min-w-[9rem] flex-col items-end gap-1' : 'flex flex-wrap items-center gap-2']) }}>
        @if ($enrollment->canSendNow())
            <form method="post" action="{{ route('sequences.send-now', $enrollment) }}">
                @csrf
                <button
                    type="submit"
                    @class([
                        'font-semibold',
                        $isTable ? 'link-action text-xs' : 'btn-primary text-xs px-3 py-1.5',
                    ])
                    onclick="return confirm('Send {{ strtolower($enrollment->sendNowLabel()) }} for {{ $contact?->name ?? 'this contact' }}?')"
                >
                    {{ $enrollment->sendNowLabel() }}
                </button>
            </form>
        @endif

        @if ($enrollment->canMarkCurrentStepComplete())
            <form method="post" action="{{ route('sequences.mark-step', $enrollment) }}">
                @csrf
                <button
                    type="submit"
                    @class([
                        $isTable ? 'link-action text-xs' : 'btn-secondary text-xs px-3 py-1.5',
                    ])
                    onclick="return confirm('Mark {{ strtolower($enrollment->markStepCompleteLabel()) }} without sending? The sequence will advance to the next step.')"
                >
                    {{ $enrollment->markStepCompleteLabel() }}
                </button>
            </form>
        @endif

        @if ($canRetry)
            <form method="post" action="{{ route('sequences.retry', $enrollment) }}">
                @csrf
                <button
                    type="submit"
                    @class([
                        $isTable ? 'link-action text-xs' : 'btn-secondary text-xs px-3 py-1.5',
                    ])
                    onclick="return confirm('Reactivate this sequence and try sending again now?')"
                >
                    Retry send
                </button>
            </form>
        @endif

        @if ($enrollment->isActive() && $contact)
            <form method="post" action="{{ route('contacts.sequence.cancel', $contact) }}" onsubmit="return confirm('Cancel automated follow-ups for {{ $contact->name }}?')">
                @csrf
                <button type="submit" class="link-danger text-xs">
                    Cancel sequence
                </button>
            </form>
        @endif
    </div>
@endcan
