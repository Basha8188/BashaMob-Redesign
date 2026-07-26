<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>متابعة {{ $repair->ticket_number }} | BashaMob</title>
    <x-theme-bootstrap />
    @vite(['resources/css/app.css','resources/css/violet.css','resources/js/theme-manager.js'])
</head>
<body class="storefront-body public-portal">
<x-aurora-background/>
<x-theme-picker/>
<main class="public-card glass-panel">
    <header><strong class="luminous-brand">Basha<span>Mob</span></strong><small>متابعة الصيانة</small></header>
    <h1>{{ $repair->ticket_number }}</h1>
    <p>{{ $repair->device?->brand }} {{ $repair->device?->model }}</p>
    <div class="passport-timeline large">
        @foreach($repair->statusHistory->sortByDesc('created_at') as $event)
            <article><i></i><div><strong>{{ $event->to_status }}</strong><p>{{ $event->notes }}</p><small>{{ $event->created_at->translatedFormat('j F Y، g:i a') }}</small></div></article>
        @endforeach
    </div>
</main>
</body>
</html>
