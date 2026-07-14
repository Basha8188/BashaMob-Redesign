@extends('layouts.auth')
@section('title','تسجيل الدخول')
@section('content')
<main class="login-page">
<section class="login-glass">
    <a class="login-logo" href="{{ route('home') }}"><span class="orbit-logo"><i></i><b>B</b></span><strong>Basha<span>Mob</span></strong><small>إدارة المبيعات، الصيانة والمخزون</small></a>
    <div class="login-title"><h1>مرحبًا بك مجددًا</h1><p>سجل الدخول للوصول إلى حسابك</p></div>
    <form class="login-form" action="{{ route('staff.login.store') }}" method="POST">
        @csrf
        @if($errors->any())<div class="login-error" role="alert">{{ $errors->first() }}</div>@endif
        <label>البريد الإلكتروني<div><svg viewBox="0 0 24 24"><path d="M3 6h18v12H3V6Zm0 1 9 7 9-7"/></svg><input type="email" name="email" value="{{ old('email', 'admin@bashamob.local') }}" required autocomplete="username"></div></label>
        <label>كلمة المرور<div><svg viewBox="0 0 24 24"><path d="M6 10h12v10H6V10Zm3 0V7a3 3 0 0 1 6 0v3"/></svg><input id="login-password" name="password" type="password" required autocomplete="current-password"><button class="password-toggle" type="button" data-target="login-password">◉</button></div></label>
        <div class="login-options"><label><input type="checkbox" name="remember" value="1"> تذكرني</label><span>دخول الموظفين فقط</span></div>
        <button class="login-submit" type="submit"><span>تسجيل الدخول</span><b>←</b><i></i></button>
    </form>
    <div class="demo-login"><b>بيانات العرض</b><span>admin@bashamob.local</span><span>password</span></div>
    <p class="customer-login-link">هل أنت عميل؟ <a href="{{ route('customer.device-request') }}">سجّل بيانات جهازك</a></p>
</section>
<p class="auth-copyright">© {{ date('Y') }} BashaMob. جميع الحقوق محفوظة.</p>
</main>
@endsection
