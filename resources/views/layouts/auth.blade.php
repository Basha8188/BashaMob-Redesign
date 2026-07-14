<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | BashaMob</title>
    <script>document.documentElement.dataset.theme=localStorage.getItem('basha-theme')||'light';document.documentElement.dataset.palette=localStorage.getItem('basha-palette')||'ocean';</script>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="auth-body">
    <div class="auth-aurora" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    <button class="auth-theme theme-toggle" type="button" aria-label="تبديل الوضع"><svg class="sun" viewBox="0 0 24 24"><path d="M12 4V2m0 20v-2m8-8h2M2 12h2m13.7-5.7 1.4-1.4M4.9 19.1l1.4-1.4m11.4 0 1.4 1.4M4.9 4.9l1.4 1.4M17 12a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z"/></svg><svg class="moon" viewBox="0 0 24 24"><path d="M20 15.3A8.5 8.5 0 0 1 8.7 4a8.5 8.5 0 1 0 11.3 11.3Z"/></svg></button>
    <x-theme-picker />
    @yield('content')
</body>
</html>
