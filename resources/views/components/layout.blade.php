@props(['title' => 'tavnit admin'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body>
    <header>
        <a href="{{ route('admin.refill-spots.index') }}">{{ __('ui.nav.refill_spots') }}</a>
        <a href="{{ route('admin.applications.index') }}">{{ __('ui.nav.applications') }}</a>

        @auth
            <span>{{ auth()->user()->email }}</span>

            {{-- Each language name is written in its own language, so anyone can find theirs. --}}
            <form method="POST" action="{{ route('locale.update') }}" style="display: inline" aria-label="{{ __('ui.language') }}">
                @csrf
                @method('PUT')
                <button type="submit" name="locale" value="ja" lang="ja" @if (app()->getLocale() === 'ja') aria-current="true" disabled @endif>日本語</button>
                <button type="submit" name="locale" value="en" lang="en" @if (app()->getLocale() === 'en') aria-current="true" disabled @endif>English</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="display: inline">
                @csrf
                <button type="submit">{{ __('ui.log_out') }}</button>
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
