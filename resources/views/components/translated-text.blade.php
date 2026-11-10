@props(['record', 'field'])

{{--
    A shop's free text. On English pages: the saved English, labelled as
    machine translated, with the Japanese one click away. If there is no
    English yet (or on Japanese pages), the Japanese as written.
    Always printed with {{ }}, so text from Google can't add HTML to the page.
--}}
@php
    $original = $record->getAttribute($field);
    $english = app()->getLocale() === 'ja' ? null : $record->savedTranslation($field, app()->getLocale());
@endphp

@if (blank($original))
    {{ __('ui.none') }}
@elseif ($english !== null)
    <span style="white-space: pre-line">{{ $english }}</span>
    <small>({{ __('ui.machine_translated') }})</small>
    <details>
        <summary>{{ __('ui.show_original') }}</summary>
        <span lang="ja" style="white-space: pre-line">{{ $original }}</span>
    </details>
@else
    <span lang="ja" style="white-space: pre-line">{{ $original }}</span>
@endif
