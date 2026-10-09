<x-layout title="Refill spots">
    <h1>Refill spots</h1>

    <p><a href="{{ route('admin.refill-spots.create') }}">Add a spot</a></p>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Location</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($spots as $spot)
                <tr>
                    <td>{{ $spot->name }}</td>
                    <td>{{ $spot->latitude }}, {{ $spot->longitude }}</td>
                    <td>
                        @if ($spot->trashed())
                            Removed
                        @elseif ($spot->is_active)
                            Active
                        @else
                            Inactive
                        @endif
                    </td>
                    <td>
                        @if ($spot->trashed())
                            <form method="POST" action="{{ route('admin.refill-spots.restore', $spot) }}">
                                @csrf
                                <button type="submit">Restore</button>
                            </form>
                        @else
                            <a href="{{ route('admin.refill-spots.edit', $spot) }}">Edit</a>
                            <form method="POST" action="{{ route('admin.refill-spots.destroy', $spot) }}"
                                  onsubmit="return confirm('Remove this spot?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Remove</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No spots yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $spots->links('pagination::bootstrap-5') }}
</x-layout>
