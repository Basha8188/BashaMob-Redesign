<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') | BashaMob</title>
    <script>document.documentElement.dataset.theme='dark';document.documentElement.dataset.palette='violet';</script>
    @vite(['resources/css/app.css','resources/css/auth.css','resources/css/violet.css','resources/js/app.js'])
</head>
<body class="auth-body @yield('body-class')">
    <x-aurora-background variant="auth" />
    @yield('content')
    <x-ai-assistant />
</body>
</html>
