<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <title>@yield('title') | BashaMob</title>
    <script>(() => { let language = 'ar'; try { language = localStorage.getItem('basha-admin-language') === 'en' ? 'en' : 'ar'; } catch {} document.documentElement.lang = language; document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr'; })();</script>
    <x-theme-bootstrap />
    @vite(['resources/css/auth.css','resources/css/assistant.css','resources/css/auth-theme.css','resources/js/auth.js'])
</head>
<body class="auth-body @yield('body-class')">
    <x-aurora-background variant="auth" />
    @yield('content')
    <x-ai-assistant />
</body>
</html>
