<x-layout title="Partner applications">
    <h1>Partner applications</h1>

    <nav>
        @foreach ($states as $tab)
            <a href="{{ route('admin.applications.index', ['state' => $tab->value]) }}"
               @if ($tab === $state) aria-current="page" @endif>
                {{ $tab->label() }} ({{ $counts[$tab->value] ?? 0 }})
            </a>
        @endforeach
    </nav>

    <table>
        <thead>
            <tr>
                <th>Shop</th>
                <th>Type</th>
                <th>Sent</th>
                <th>Reviewer</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>{{ $application->store_name }}</td>
                    <td>{{ $application->store_type }}</td>
                    <td>{{ $application->created_at->diffForHumans() }}</td>
                    <td>{{ $application->claimedBy?->name ?? '—' }}</td>
                    <td><a href="{{ route('admin.applications.show', $application) }}">Open</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nothing in this list.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $applications->links('pagination::bootstrap-5') }}
</x-layout>
