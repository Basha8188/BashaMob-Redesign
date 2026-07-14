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
<body>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <x-navbar :active="$activePage ?? 'home'" />
    <main class="site-main">@yield('content')</main>
    <x-footer />
    <x-theme-picker />
</body>
</html>
