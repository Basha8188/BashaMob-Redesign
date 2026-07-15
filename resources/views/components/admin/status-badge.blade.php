@props(['tone' => 'success'])
<span {{ $attributes->class("status-badge status-$tone") }}>{{ $slot }}</span>
