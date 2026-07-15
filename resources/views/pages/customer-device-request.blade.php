@extends('layouts.auth')
@section('title','تسجيل جهاز العميل')
@section('body-class','customer-portal-body')
@section('content')
<div class="customer-device-rain" aria-hidden="true">@for($i=1;$i<=12;$i++)<i class="{{ $i%3===0?'mini-laptop':'mini-phone' }}" style="--x:{{ ($i*31)%97 }}%;--delay:-{{ $i*2 }}s;--speed:{{ 15+($i%6) }}s"></i>@endfor @for($i=1;$i<=4;$i++)<span style="--x:{{ ($i*23+11)%92 }}%;--delay:-{{ $i*5 }}s;--speed:{{ 19+$i }}s">BashaMob</span>@endfor</div>
<div class="customer-welcome" id="customer-welcome"><div><span class="orbit-logo"><i></i><b>B</b></span><small>أهلًا بك في</small><h1>باشا موب</h1><p>تقنية تثق بها، وخدمة تليق بك</p><button type="button" id="skip-welcome">ابدأ الآن</button></div></div>
<main class="customer-request-page is-waiting" id="customer-request-page"><section class="customer-request-card">
    <div class="request-intro"><a class="login-logo" href="{{ route('home') }}"><span class="orbit-logo"><i></i><b>B</b></span><strong>Basha<span>Mob</span></strong></a><span class="eyebrow">بوابة العملاء</span><h1>سجّل بيانات جهازك</h1><p>املأ البيانات وارفع صورة الجهاز إن كانت متوفرة، وسيتواصل معك فريق الصيانة.</p><div class="request-points"><span>✓ رقم طلب واضح للمتابعة</span><span>✓ خصوصية كاملة لبياناتك</span><span>✓ تواصل سريع مع الفني</span></div></div>
    <form class="customer-device-form">
        <h2>بيانات العميل</h2><div class="request-grid"><label>الاسم بالكامل<input required placeholder="اكتب اسمك"></label><label>رقم الموبايل<input required type="tel" placeholder="01xxxxxxxxx"></label><label>رقم بديل<input type="tel" placeholder="اختياري"></label><label>البريد الإلكتروني<input type="email" placeholder="اختياري"></label></div>
        <h2>بيانات الجهاز</h2><div class="request-grid"><label>نوع الجهاز<select><option>موبايل</option><option>لابتوب</option><option>كمبيوتر</option><option>تابلت</option></select></label><label>الماركة<input placeholder="مثال: Apple"></label><label>الموديل<input placeholder="مثال: iPhone 13 Pro"></label><label>الرقم التسلسلي / IMEI<input placeholder="إن كان متوفرًا"></label></div>
        <label>وصف المشكلة<textarea required placeholder="اشرح المشكلة ومتى بدأت..."></textarea></label>
        <label class="device-upload"><input type="file" accept="image/*"><span>＋</span><strong>ارفع صورة الجهاز</strong><small>PNG أو JPG — حد أقصى 5MB</small></label>
        <label class="consent"><input type="checkbox" required> أوافق على استخدام البيانات للتواصل بخصوص طلب الصيانة.</label>
        <button class="login-submit" type="submit"><span>إرسال طلب الصيانة</span><b>←</b><i></i></button>
    </form>
</section></main>
@endsection
