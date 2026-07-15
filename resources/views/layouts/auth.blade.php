<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | BashaMob</title>
    <script>document.documentElement.dataset.theme='light';document.documentElement.dataset.palette='ocean';</script>
    @vite(['resources/css/app.css','resources/css/auth.css','resources/js/app.js'])
</head>
<body class="auth-body @yield('body-class')">
    <div class="auth-aurora" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
    @yield('content')
</body>
</html>
