<x-layout title="Edit {{ $spot->name }}">
    <h1>Edit {{ $spot->name }}</h1>

    <form method="POST" action="{{ route('admin.refill-spots.update', $spot) }}">
        @method('PUT')
        @include('admin.refill-spots.form')
        <button type="submit">Save</button>
    </form>

    <p><a href="{{ route('admin.refill-spots.index') }}">Back to the list</a></p>
</x-layout>
