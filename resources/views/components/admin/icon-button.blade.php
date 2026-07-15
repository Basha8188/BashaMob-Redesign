@props(['label'])
<button type="button" {{ $attributes->merge(['aria-label' => $label]) }}>{{ $slot }}</button>
