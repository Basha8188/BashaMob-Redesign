@props(['active' => 'home'])

<header class="nav-wrap">
    <a class="brand" href="{{ route('home') }}" aria-label="العودة للرئيسية">
        <x-brand-symbol class="brand-mark"/><span>Basha<b>Mob</b></span>
    </a>

    <nav data-liquid-nav="horizontal" class="liquid-nav" aria-label="التنقل الرئيسي">
        <span class="nav-liquid-indicator" data-liquid-indicator aria-hidden="true"></span>
        @foreach ([
            ['home', 'home', 'الرئيسية', 'M3 11.5 12 4l9 7.5M5.5 10v10h13V10M9.5 20v-6h5v6'],
            ['shop', 'shop', 'المتجر', 'M6 8h12l1 12H5L6 8Zm3 0V6a3 3 0 0 1 6 0v2'],
            ['maintenance', 'maintenance', 'الصيانة', 'm14.7 6.3 3-3a5 5 0 0 1-6.4 6.4l-7.6 7.6a1.4 1.4 0 0 0 2 2l7.6-7.6a5 5 0 0 1 6.4-6.4l-3 3-2-2Z'],
            ['computers', 'computers', 'الكمبيوتر', 'M4 5h16v11H4V5Zm5 15h6m-4-4v4m2-4v4'],
        ] as [$key, $route, $label, $path])
            <a data-liquid-nav-item href="{{ route($route) }}" class="nav-link {{ $active === $key ? 'is-active' : '' }}" aria-current="{{ $active === $key ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $path }}"/></svg>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</header>
