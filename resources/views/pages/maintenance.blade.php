@extends('layouts.app')
@section('title', 'الصيانة')
@php($activePage = 'maintenance')
@section('content')
<x-page-hero eyebrow="صيانة باحتراف" title="هنرجّع جهازك كأنه جديد" text="احجز طلبك في دقيقة، وسيب الباقي على فنيين باشا موب." />
<div class="container customer-portal-link"><span>هل تريد تسجيل بيانات الجهاز وصورته بالتفصيل؟</span><a class="btn btn-glass" href="{{ route('customer.device-request') }}">فتح شاشة العميل ←</a></div>
<section class="container section compact two-column">
    <div class="steps-card">
        <h2>رحلة صيانة واضحة</h2>
        @foreach([['01','استلام وفحص','بنفحص الجهاز ونسجل كل التفاصيل.'],['02','تأكيد التكلفة','السعر بيوصلك قبل بدء أي إصلاح.'],['03','إصلاح واختبار','إصلاح احترافي واختبار كامل للجهاز.'],['04','جاهز للاستلام','بنبلغك فور ما جهازك يكون جاهز.']] as $step)
            <div class="step"><b>{{ $step[0] }}</b><span><strong>{{ $step[1] }}</strong><small>{{ $step[2] }}</small></span></div>
        @endforeach
    </div>
    <form class="glass-form">
        <span class="eyebrow">طلب صيانة جديد</span><h2>احكيلنا عن جهازك</h2>
        <label>الاسم<input type="text" placeholder="الاسم بالكامل"></label>
        <label>رقم الموبايل<input type="tel" placeholder="01xxxxxxxxx"></label>
        <div class="form-row"><label>نوع الجهاز<select><option>موبايل</option><option>لابتوب</option><option>كمبيوتر</option></select></label><label>الماركة<input type="text" placeholder="مثال: Apple"></label></div>
        <label>وصف المشكلة<textarea placeholder="اكتب تفاصيل المشكلة..."></textarea></label>
        <button class="btn btn-primary" type="button">إرسال الطلب</button>
    </form>
</section>
@endsection
