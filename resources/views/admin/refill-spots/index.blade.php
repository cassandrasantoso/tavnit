<x-layout :title="__('spots.title')">
    <h1>{{ __('spots.title') }}</h1>

    <p><a href="{{ route('admin.refill-spots.create') }}">{{ __('spots.add_link') }}</a></p>

    <table>
        <thead>
            <tr>
                <th>{{ __('spots.columns.name') }}</th>
                <th>{{ __('spots.columns.location') }}</th>
                <th>{{ __('spots.columns.status') }}</th>
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
                            {{ __('spots.status.removed') }}
                        @elseif ($spot->is_active)
                            {{ __('spots.status.active') }}
                        @else
                            {{ __('spots.status.inactive') }}
                        @endif
                    </td>
                    <td>
                        @if ($spot->trashed())
                            <form method="POST" action="{{ route('admin.refill-spots.restore', $spot) }}">
                                @csrf
                                <button type="submit">{{ __('spots.restore') }}</button>
                            </form>
                        @else
                            <a href="{{ route('admin.refill-spots.edit', $spot) }}">{{ __('spots.edit') }}</a>
                            <form method="POST" action="{{ route('admin.refill-spots.destroy', $spot) }}"
                                  onsubmit="return confirm(@js(__('spots.confirm_remove')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit">{{ __('spots.remove') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">{{ __('spots.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $spots->links('pagination::bootstrap-5') }}
</x-layout>
