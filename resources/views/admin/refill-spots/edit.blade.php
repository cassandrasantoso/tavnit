<x-layout :title="__('spots.edit_title', ['name' => $spot->name])">
    <h1>{{ __('spots.edit_title', ['name' => $spot->name]) }}</h1>

    <form method="POST" action="{{ route('admin.refill-spots.update', $spot) }}">
        @method('PUT')
        @include('admin.refill-spots.form')
        <button type="submit">{{ __('spots.save') }}</button>
    </form>

    <p><a href="{{ route('admin.refill-spots.index') }}">{{ __('ui.back_to_list') }}</a></p>
</x-layout>
