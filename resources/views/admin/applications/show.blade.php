<x-layout :title="$application->store_name">
    <p><a href="{{ route('admin.applications.index', ['state' => $application->state->value]) }}">Back to the list</a></p>

    <h1>{{ $application->store_name }}</h1>
    <p>{{ $application->state->label() }} · sent {{ $application->created_at->diffForHumans() }}</p>

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
