<x-layout title="Add a refill spot">
    <h1>Add a refill spot</h1>

    <form method="POST" action="{{ route('admin.refill-spots.store') }}">
        @include('admin.refill-spots.form')
        <button type="submit">Add spot</button>
    </form>

    <p><a href="{{ route('admin.refill-spots.index') }}">Back to the list</a></p>
</x-layout>
