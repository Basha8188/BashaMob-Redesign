<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#8b7cf6">
    <title>@yield('title', 'BashaMob') | باشا موب</title>
    <meta name="description" content="باشا موب لبيع وصيانة الهواتف والكمبيوتر والإكسسوارات">
    <script>document.documentElement.dataset.theme=localStorage.getItem('basha-theme')||'light';document.documentElement.dataset.palette=localStorage.getItem('basha-palette')||'ocean';</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="storefront-body">
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="storefront-device-rain" aria-hidden="true">
        @for($device = 1; $device <= 15; $device++)
            <i class="{{ $device % 3 === 0 ? 'customer-falling-laptop' : 'customer-falling-phone' }}" style="--x:{{ ($device * 37) % 101 }}%;--delay:-{{ $device * 2 }}s;--duration:{{ 17 + ($device % 6) }}s;--scale:{{ (68 + (($device * 9) % 38)) / 100 }}"></i>
        @endfor
        @for($brand = 1; $brand <= 5; $brand++)
            <span style="--x:{{ ($brand * 29 + 8) % 95 }}%;--delay:-{{ $brand * 4 }}s;--duration:{{ 20 + $brand }}s">BashaMob</span>
        @endfor
    </div>
    <x-navbar :active="$activePage ?? 'home'" />
    <main class="site-main">@yield('content')</main>
    <div class="storefront-toast" id="storefront-toast" role="status"></div>
    <x-footer />
</body>
</html>
