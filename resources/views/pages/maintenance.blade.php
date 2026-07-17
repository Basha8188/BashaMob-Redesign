@extends('layouts.app')
@section('title', 'الصيانة')
@php($activePage = 'maintenance')
@section('content')
<x-page-hero eyebrow="صيانة باحتراف" title="هنرجّع جهازك كأنه جديد" text="احجز طلبك في دقيقة، وسيب الباقي على فنيين باشا موب." />
<section class="container section compact two-column">
    <div class="steps-card">
        <h2>رحلة صيانة واضحة</h2>
        @foreach([['01','استلام وفحص','بنفحص الجهاز ونسجل كل التفاصيل.'],['02','تأكيد التكلفة','السعر بيوصلك قبل بدء أي إصلاح.'],['03','إصلاح واختبار','إصلاح احترافي واختبار كامل للجهاز.'],['04','جاهز للاستلام','بنبلغك فور ما جهازك يكون جاهز.']] as $step)
            <div class="step"><b>{{ $step[0] }}</b><span><strong>{{ $step[1] }}</strong><small>{{ $step[2] }}</small></span></div>
        @endforeach
    </div>
    <form class="glass-form" data-quick-maintenance action="{{ route('maintenance.requests.store') }}" method="POST">
        @csrf
        <span class="eyebrow">طلب صيانة جديد</span><h2>احكيلنا عن جهازك</h2>
        <label>الاسم<input name="name" required type="text" placeholder="الاسم بالكامل"></label>
        <div class="form-row"><label>رقم الموبايل<input name="phone" required type="tel" pattern="01[0-9]{9}" placeholder="01xxxxxxxxx"></label><label>البريد الإلكتروني<input name="email" required type="email" placeholder="name@example.com"></label></div>
        <div class="form-row"><label>نوع الجهاز<select name="device"><option>موبايل</option><option>لابتوب</option><option>كمبيوتر</option></select></label><label>الماركة<input name="brand" required type="text" placeholder="مثال: Apple"></label></div>
        <label>وصف المشكلة<textarea name="problem" required placeholder="اكتب تفاصيل المشكلة..."></textarea></label>
        <button class="btn btn-primary" type="submit">إرسال الطلب</button><p class="quick-maintenance-result" role="status"></p>
    </form>
</section>
<section class="container maintenance-tracking glass-form">
    <div class="tracking-copy"><span class="eyebrow">متابعة سريعة</span><h2>استعلم عن طلبك</h2><p>اكتب رقم الطلب، رقم الموبايل، أو الاسم بالكامل لمعرفة آخر حالة مسجلة.</p></div>
    <form data-maintenance-track action="{{ route('maintenance.requests.track') }}" method="POST">@csrf<label><span class="sr-only">بيانات البحث عن الطلب</span><input name="query" required minlength="3" type="search" placeholder="مثال: BM-2026-123456 أو 01xxxxxxxxx"><button class="btn btn-primary" type="submit">استعلام عن الطلب</button></label></form>
    <div class="tracking-results" data-tracking-results aria-live="polite"></div>
</section>
<div class="request-launch" data-request-launch aria-hidden="true">
    <div class="delivery-scene" aria-hidden="true">
        <div class="delivery-stars"></div>
        <span class="dispatch-lamp"><i></i><b></b></span>
        <span class="dispatch-worker">
            <svg viewBox="0 0 86 110"><circle cx="45" cy="19" r="12"/><path d="M34 34h23l7 35H27l7-35Z"/><path class="worker-arm" d="M57 40c14 4 18 11 22 19"/><path d="m34 68-9 34m31-34 10 34"/></svg>
        </span>
        <span class="delivery-ticket"><b>طلب صيانة</b><small data-launch-number></small><i>✓</i></span>
        <span class="delivery-hub"><x-brand-symbol/><b>BashaMob</b><small>مركز الاستقبال</small></span>
        <span class="transport-truck">
            <svg viewBox="0 0 210 100"><path class="truck-box" d="M75 14h112v58H75z"/><path class="truck-cab" d="M18 38h57v34H8V54l10-16Z"/><path class="truck-window" d="M25 43h25l13 20H20l5-20Z"/><path class="truck-line" d="M88 31h83M99 47h62"/><circle cx="48" cy="77" r="14"/><circle cx="161" cy="77" r="14"/><circle class="wheel-core" cx="48" cy="77" r="5"/><circle class="wheel-core" cx="161" cy="77" r="5"/></svg>
            <b>BashaMob</b><i></i>
        </span>
        <span class="delivery-road"><i></i></span>
        <span class="delivery-arrived">✓<b>وصل الطلب</b></span>
    </div>
    <strong data-delivery-status aria-live="polite">بنفتح خط الاستقبال لطلبك...</strong><small>استلام سريع، نقل آمن، ومتابعة دقيقة من BashaMob</small>
</div>
@endsection
