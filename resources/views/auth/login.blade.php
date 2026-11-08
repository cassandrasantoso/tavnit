<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('login.title') }} · tavnit</title>
</head>
<body>
    <h1>{{ __('login.title') }}</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">{{ __('login.email') }}</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')
            <p>{{ $message }}</p>
        @enderror

        <label for="password">{{ __('login.password') }}</label>
        <input id="password" type="password" name="password" required>

        <button type="submit">{{ __('login.submit') }}</button>
    </form>
</body>
</html>
