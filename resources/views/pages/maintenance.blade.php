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
@endsection
