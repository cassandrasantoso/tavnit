<x-layout :title="__('spots.create_title')">
    <h1>{{ __('spots.create_title') }}</h1>

    <form method="POST" action="{{ route('admin.refill-spots.store') }}">
        @include('admin.refill-spots.form')
        <button type="submit">{{ __('spots.add_button') }}</button>
    </form>

    <p><a href="{{ route('admin.refill-spots.index') }}">{{ __('ui.back_to_list') }}</a></p>
</x-layout>
