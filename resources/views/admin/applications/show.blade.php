<x-layout :title="$application->store_name">
    <p><a href="{{ route('admin.applications.index', ['state' => $application->state->value]) }}">Back to the list</a></p>

    @error('application')
        <p role="alert">{{ $message }}</p>
    @enderror

    @if ($duplicates->isNotEmpty())
        <div role="alert">
            <p><strong>Possible duplicate.</strong> These spots have the same name and are within {{ config('tavnit.review.duplicate_radius_m') }} m:</p>
            <ul>
                @foreach ($duplicates as $match)
                    <li>
                        <a href="{{ route('admin.refill-spots.edit', $match['spot']) }}">{{ $match['spot']->name }}</a>
                        ({{ $match['distance_m'] }} m away)
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <h1>{{ $application->store_name }}</h1>
    <p>{{ $application->state->label() }} · sent {{ $application->created_at->diffForHumans() }}</p>

    @if ($application->state->canMoveTo(\App\Enums\ApplicationState::InReview))
        <form method="POST" action="{{ route('admin.applications.claim', $application) }}">
            @csrf
            {{-- The version this page was loaded with. The claim only works if it still matches. --}}
            <input type="hidden" name="version" value="{{ $application->version }}">
            <button type="submit">Claim</button>
        </form>
    @endif

    <dl>
        <dt>Type</dt>
        <dd>{{ $application->store_type }}</dd>

        <dt>Address</dt>
        <dd>{{ $application->address }}</dd>

        <dt>Location</dt>
        <dd>{{ $application->latitude }}, {{ $application->longitude }}</dd>

        <dt>Description</dt>
        <dd style="white-space: pre-line">{{ $application->description_ja ?? '—' }}</dd>

        <dt>Opening hours</dt>
        <dd style="white-space: pre-line">{{ $application->hours_note_ja ?? '—' }}</dd>

        <dt>Message from the shop</dt>
        <dd style="white-space: pre-line">{{ $application->message_ja ?? '—' }}</dd>

        <dt>Opening date</dt>
        <dd>{{ $application->opening_date?->toDateString() ?? '—' }}</dd>

        <dt>Contact</dt>
        <dd>{{ $application->contact_email }}</dd>

        <dt>Reviewer</dt>
        <dd>{{ $application->claimedBy?->name ?? 'Nobody yet' }}</dd>
    </dl>
</x-layout>
