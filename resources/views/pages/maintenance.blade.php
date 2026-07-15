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
    <div class="launch-sky">
        <span class="launch-order">طلبك <b data-launch-number></b></span>
        <span class="launch-rocket"><i></i><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M37 7c9 2 16 9 18 18L38 42 22 26 37 7Z"/><path d="m23 27-10 3-5 9 17-2m12 2-3 11-9 5 2-17"/><circle cx="40" cy="22" r="5"/><path class="rocket-flame" d="m18 40-8 11 12-7"/></svg></span>
        <span class="launch-destination"><x-brand-symbol/> <b>BashaMob</b></span>
    </div>
    <strong>الصاروخ استلم طلبك ووصّله لنا!</strong><small>سرعة في الاستلام… ودقة في المتابعة</small>
</div>
@endsection
