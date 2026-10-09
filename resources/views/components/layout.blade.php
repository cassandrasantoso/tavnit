@props(['title' => 'tavnit admin'])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body>
    <header>
        <a href="{{ route('admin.refill-spots.index') }}">Refill spots</a>

        @auth
            <span>{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}" style="display: inline">
                @csrf
                <button type="submit">Log out</button>
            </form>
        @endauth
    </header>

    @if (session('status'))
        <p role="status">{{ session('status') }}</p>
    @endif

    <main>
        {{ $slot }}
    </main>
</body>
</html>
