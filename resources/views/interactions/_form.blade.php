<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="label" for="contact_id">Contact</label>
        <select class="input" id="contact_id" name="contact_id" required>
            <option value="">Select contact</option>
            @foreach ($contacts as $contactOption)
                <option value="{{ $contactOption->id }}" @selected((string) old('contact_id', $interaction->contact_id ?? request('contact_id')) === (string) $contactOption->id)>
                    {{ $contactOption->name }}{{ $contactOption->company ? ' - '.$contactOption->company : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="campaign_id">Campaign</label>
        <select class="input" id="campaign_id" name="campaign_id">
            <option value="">No campaign</option>
            @foreach ($campaigns as $campaignOption)
                <option value="{{ $campaignOption->id }}" @selected((string) old('campaign_id', $interaction->campaign_id ?? request('campaign_id')) === (string) $campaignOption->id)>
                    {{ $campaignOption->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="channel">Channel</label>
        <select class="input" id="channel" name="channel" required>
            @foreach (\App\Enums\InteractionChannel::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('channel', $interaction->channel?->value ?? $interaction->channel ?? 'email') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label" for="direction">Direction</label>
        <select class="input" id="direction" name="direction" required>
            @foreach (\App\Enums\InteractionDirection::options() as $value => $label)
                <option value="{{ $value }}" @selected(old('direction', $interaction->direction?->value ?? $interaction->direction ?? 'outbound') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="content">Content</label>
        <textarea class="input min-h-36" id="content" name="content" required>{{ old('content', $interaction->content) }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="label" for="sent_at">Sent at</label>
        <input class="input" id="sent_at" name="sent_at" type="datetime-local" value="{{ old('sent_at', optional($interaction->sent_at)->format('Y-m-d\TH:i')) }}">
    </div>
</div>

<div class="mt-8 border-t border-slate-800 pt-8">
    <h3 class="text-lg font-semibold text-white">Optional response</h3>
    <p class="mt-1 text-sm text-slate-500">Capture the outcome directly when logging the interaction.</p>

    <div class="mt-5 grid gap-5 md:grid-cols-2">
        <div>
            <label class="label" for="response_outcome">Outcome</label>
            <select class="input" id="response_outcome" name="response[outcome]">
                <option value="">No response yet</option>
                @foreach (\App\Enums\ResponseOutcome::options() as $value => $label)
                    <option value="{{ $value }}" @selected(old('response.outcome', $interaction->response?->outcome?->value) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label" for="response_follow_up_date">Follow-up date</label>
            <input class="input" id="response_follow_up_date" name="response[follow_up_date]" type="date" value="{{ old('response.follow_up_date', optional($interaction->response?->follow_up_date)->format('Y-m-d')) }}">
        </div>
        <div class="md:col-span-2">
            <label class="label" for="response_sentiment_notes">Sentiment notes</label>
            <textarea class="input min-h-28" id="response_sentiment_notes" name="response[sentiment_notes]">{{ old('response.sentiment_notes', $interaction->response?->sentiment_notes) }}</textarea>
        </div>
    </div>
</div>
