@extends('layouts.auth')
@section('title','Sign In')
@section('content')
<main class="reference-login-page">
    <section class="reference-login-card">
        <header class="reference-login-heading">
            <h1>Welcome To <span class="auth-luminous-brand">BashaMob</span></h1>
            <p>Sign in to your account</p>
        </header>
        <form class="reference-login-form" action="{{ route('staff.login.store') }}" method="POST">
            @csrf
            @if($errors->any())<div class="login-error" role="alert">{{ $errors->first() }}</div>@endif
            <label class="reference-field"><span class="sr-only">Email Address</span><input type="email" name="email" value="{{ old('email', 'admin@bashamob.local') }}" placeholder="Email Address" required autocomplete="username"></label>
            <label class="reference-field password-field"><span class="sr-only">Password</span><input id="login-password" name="password" type="password" placeholder="Password" required autocomplete="current-password"><button class="password-toggle reference-eye" type="button" data-target="login-password" aria-label="Show password"><svg viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></label>
            <div class="reference-login-options"><label><input type="checkbox" name="remember" value="1"><i></i><span>Remember me</span></label><button type="button" data-recovery-open data-recovery-method="email">Forgot password?</button></div>
            <button class="reference-signin" type="submit"><span>Sign In</span><i></i></button>
        </form>
        <div class="reference-divider"><span>استرداد كلمة المرور عبر</span></div>
        <div class="social-login-row recovery-channels"><button type="button" data-recovery-open data-recovery-method="email"><span class="google-mark">G</span>Gmail</button><button type="button" data-recovery-open data-recovery-method="whatsapp"><svg class="whatsapp-mark" viewBox="0 0 24 24"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3 20.5l1.4-4.7a8.5 8.5 0 1 1 16.1-4.1Z"/><path d="M8.2 7.7c.3-.6.6-.6.9-.6h.5c.2 0 .4.1.5.5l.8 2c.1.3 0 .5-.2.7l-.7.8c-.2.2-.2.4 0 .7.7 1.2 1.7 2.2 3 2.8.3.2.5.1.7-.1l.9-1.1c.2-.2.4-.3.7-.2l2 .9c.3.1.5.3.5.5 0 .4-.2 1.5-.8 2.1-.6.6-1.5.9-2.4.7-1.3-.3-3.8-1.2-5.9-3.1-1.7-1.5-2.9-3.5-3.2-4.8-.3-1.2.2-2.5.7-3.1Z"/></svg>WhatsApp</button></div>
        <p class="reference-signup">Customer? <a href="{{ route('maintenance') }}">Send a maintenance request</a></p>
    </section>
    <section class="recovery-modal" id="recovery-modal" aria-hidden="true">
        <button class="recovery-backdrop" type="button" data-recovery-close aria-label="Close"></button>
        <div class="recovery-dialog" role="dialog" aria-modal="true" aria-labelledby="recovery-title">
            <button class="recovery-close" type="button" data-recovery-close>×</button>
            <span class="recovery-icon">✦</span><h2 id="recovery-title">استرداد كلمة المرور</h2><p id="recovery-copy">سنرسل كودًا صالحًا لمدة 10 دقائق.</p>
            <form id="recovery-request-form" action="{{ route('staff.recovery.send') }}">
                <input type="hidden" name="method" value="email"><label><span id="recovery-destination-label">البريد الإلكتروني</span><input name="destination" type="email" autocomplete="email" required placeholder="admin@bashamob.local"></label>
                <button type="submit">إرسال كود التحقق</button>
            </form>
            <form id="recovery-verify-form" action="{{ route('staff.recovery.verify') }}" data-reset-action="{{ route('staff.recovery.reset') }}" hidden>
                <input type="hidden" name="challenge"><label><span>كود التحقق</span><input name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required placeholder="000000"></label>
                <label><span>كلمة المرور الجديدة</span><input name="password" type="password" minlength="8" required></label><label><span>تأكيد كلمة المرور</span><input name="password_confirmation" type="password" minlength="8" required></label>
                <button type="submit">حفظ كلمة المرور</button>
            </form>
            <p class="recovery-status" id="recovery-status" role="status"></p>
        </div>
    </section>
</main>
@endsection
