@props(['name', 'kana' => null, 'english' => null])

{{-- A shop's name as submitted, plus how to read it. The name itself is never translated. --}}
<span lang="ja">{{ $name }}</span>
@if (app()->getLocale() === 'ja')
    @if (filled($kana))
        <small lang="ja">（{{ $kana }}）</small>
    @endif
@else
    @if (filled($kana))
        <small lang="ja-Latn" title="{{ __('ui.reading_hint') }}">{{ \App\Support\Romaji::fromKana($kana) }}</small>
    @endif
    @if (filled($english))
        <small lang="en">“{{ $english }}”</small>
    @endif
@endif
