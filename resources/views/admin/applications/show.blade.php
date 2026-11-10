<x-layout :title="$application->store_name">
    <p><a href="{{ route('admin.applications.index', ['state' => $application->state->value]) }}">{{ __('ui.back_to_list') }}</a></p>

    @if ($machineTranslated)
        <p role="note">{{ __('ui.machine_translation_banner') }}</p>
    @endif
    @if ($translationMissing)
        <p role="status">{{ __('ui.translation_unavailable') }}</p>
    @endif

    @error('application')
        <p role="alert">{{ $message }}</p>
    @enderror

    @if ($duplicates->isNotEmpty())
        <div role="alert">
            <p><strong>{{ __('applications.duplicate_title') }}</strong> {{ __('applications.duplicate_intro', ['radius' => config('tavnit.review.duplicate_radius_m')]) }}</p>
            <ul>
                @foreach ($duplicates as $match)
                    <li>
                        <a href="{{ route('admin.refill-spots.edit', $match['spot']) }}"><x-shop-name :name="$match['spot']->name" :kana="$match['spot']->name_kana" :english="$match['spot']->name_en" /></a>
                        {{ __('applications.distance', ['distance' => $match['distance_m']]) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1><x-shop-name :name="$application->store_name" :kana="$application->store_name_kana" :english="$application->store_name_en" /></h1>
    <p>{{ $application->state->label() }} · {{ __('applications.sent', ['time' => $application->created_at->diffForHumans()]) }}</p>

    @if ($application->state->canMoveTo(\App\Enums\ApplicationState::InReview))
        <form method="POST" action="{{ route('admin.applications.claim', $application) }}">
            @csrf
            {{-- The version this page was loaded with. The claim only works if it still matches. --}}
            <input type="hidden" name="version" value="{{ $application->version }}">
            <button type="submit">{{ __('applications.claim') }}</button>
        </form>
    @endif

    @if ($application->state === \App\Enums\ApplicationState::InReview && $application->claimedBy?->is(auth()->user()))
        <form method="POST" action="{{ route('admin.applications.decide', $application) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $application->version }}">

            <fieldset>
                <legend>{{ __('applications.decision') }}</legend>
                <label><input type="radio" name="decision" value="approved" @checked(old('decision') === 'approved')> {{ __('applications.decisions.approved') }}</label>
                <label><input type="radio" name="decision" value="returned" @checked(old('decision') === 'returned')> {{ __('applications.decisions.returned') }}</label>
                <label><input type="radio" name="decision" value="rejected" @checked(old('decision') === 'rejected')> {{ __('applications.decisions.rejected') }}</label>
                @error('decision') <p role="alert">{{ $message }}</p> @enderror
            </fieldset>

            <label for="reason">{{ __('applications.reason_label') }}</label>
            <select id="reason" name="reason">
                <option value="">{{ __('ui.none') }}</option>
                @foreach (config('tavnit.review.reasons') as $code)
                    <option value="{{ $code }}" @selected(old('reason') === $code)>{{ __("review.reasons.$code") }}</option>
                @endforeach
            </select>
            @error('reason') <p role="alert">{{ $message }}</p> @enderror

            <label for="note">{{ __('applications.note_label') }}</label>
            <textarea id="note" name="note" rows="3">{{ old('note') }}</textarea>
            @error('note') <p role="alert">{{ $message }}</p> @enderror

            <button type="submit">{{ __('applications.save_decision') }}</button>
        </form>
    @endif

    @if ($application->state === \App\Enums\ApplicationState::Approved)
        <form method="POST" action="{{ route('admin.applications.publish', $application) }}">
            @csrf
            <input type="hidden" name="version" value="{{ $application->version }}">
            @if ($duplicates->isNotEmpty())
                <label><input type="checkbox" name="confirm_duplicate" value="1"> {{ __('applications.publish_anyway') }}</label>
            @endif
            @error('confirm_duplicate') <p role="alert">{{ $message }}</p> @enderror
            <button type="submit">{{ __('applications.publish') }}</button>
        </form>
    @endif

    @if ($application->spot)
        <p>{{ __('applications.published_as') }} <a href="{{ route('admin.refill-spots.edit', $application->spot) }}">{{ $application->spot->name }}</a></p>
    @endif

    <dl>
        <dt>{{ __('applications.fields.type') }}</dt>
        <dd>{{ $application->storeTypeLabel() }}</dd>

        <dt>{{ __('applications.fields.address') }}</dt>
        <dd>{{ $application->address }}</dd>

        <dt>{{ __('applications.fields.location') }}</dt>
        <dd>{{ $application->latitude }}, {{ $application->longitude }}</dd>

        <dt>{{ __('applications.fields.description') }}</dt>
        <dd><x-translated-text :record="$application" field="description_ja" /></dd>

        <dt>{{ __('applications.fields.hours') }}</dt>
        <dd><x-translated-text :record="$application" field="hours_note_ja" /></dd>

        <dt>{{ __('applications.fields.message') }}</dt>
        <dd><x-translated-text :record="$application" field="message_ja" /></dd>

        <dt>{{ __('applications.fields.opening_date') }}</dt>
        <dd>{{ $application->opening_date?->toDateString() ?? __('ui.none') }}</dd>

        <dt>{{ __('applications.fields.contact') }}</dt>
        <dd>{{ $application->contact_email }}</dd>

        <dt>{{ __('applications.fields.reviewer') }}</dt>
        <dd>{{ $application->claimedBy?->name ?? __('applications.nobody') }}</dd>
    </dl>

    <h2>{{ __('applications.history') }}</h2>
    <ol>
        @forelse ($application->stateChanges as $change)
            <li>
                {{ $change->created_at->format('Y-m-d H:i') }} · {{ $change->user?->name ?? __('applications.someone') }}:
                {{ $change->from_state->label() }} → {{ $change->to_state->label() }}
                @if ($change->reason)
                    · {{ __("review.reasons.{$change->reason}") }}
                    ({{ __("review.reasons.{$change->reason}", [], app()->getLocale() === 'ja' ? 'en' : 'ja') }})
                @endif
                @if ($change->note)
                    <div style="white-space: pre-line">{{ $change->note }}</div>
                @endif
            </li>
        @empty
            <li>{{ __('applications.no_changes') }}</li>
        @endforelse
    </ol>
</x-layout>
