@props(['title', 'count', 'link', 'icon' => 'phone'])
@php
    $icons = [
        'phone' => 'M8 3h8v18H8V3Zm3 15h2',
        'laptop' => 'M5 5h14v10H5V5Zm-2 13h18',
        'headphones' => 'M5 17v-5a7 7 0 0 1 14 0v5m-14-2H3v5h4v-5H5Zm14 0h2v5h-4v-5h2Z',
        'tools' => 'm14.5 6.5 3-3a5 5 0 0 1-6 6l-7 7a1.5 1.5 0 1 0 2 2l7-7a5 5 0 0 1 6-6l-3 3-2-2Z',
    ];
@endphp
<a href="{{ $link }}" class="category-card reveal">
    <span class="category-icon"><svg viewBox="0 0 24 24"><path d="{{ $icons[$icon] }}"/></svg></span>
    <span><strong>{{ $title }}</strong><small>{{ $count }}</small></span><b>←</b>
</a>
