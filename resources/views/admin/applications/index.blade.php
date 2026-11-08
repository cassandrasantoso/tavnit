<x-layout :title="__('applications.title')">
    <h1>{{ __('applications.title') }}</h1>

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
                <th>{{ __('applications.columns.shop') }}</th>
                <th>{{ __('applications.columns.type') }}</th>
                <th>{{ __('applications.columns.sent') }}</th>
                <th>{{ __('applications.columns.reviewer') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($applications as $application)
                <tr>
                    <td>{{ $application->store_name }}</td>
                    <td>{{ $application->storeTypeLabel() }}</td>
                    <td>{{ $application->created_at->diffForHumans() }}</td>
                    <td>{{ $application->claimedBy?->name ?? __('ui.none') }}</td>
                    <td><a href="{{ route('admin.applications.show', $application) }}">{{ __('applications.open') }}</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('applications.empty') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $applications->links('pagination::bootstrap-5') }}
</x-layout>
