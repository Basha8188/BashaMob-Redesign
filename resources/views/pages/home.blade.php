@extends('layouts.app')
@section('title', 'الرئيسية')
@php($activePage = 'home')
@section('content')
<section class="hero container">
    <div class="hero-copy reveal">
        <span class="eyebrow"><i></i> كل التكنولوجيا في مكان واحد</span>
        <h1>اختيارك الصح<br><span>يبدأ من باشا موب.</span></h1>
        <p>أحدث الموبايلات والكمبيوتر والإكسسوارات، مع مركز صيانة محترف يهتم بجهازك من أول فحص لحد الاستلام.</p>
        <div class="hero-service-note">
            <span>مركز صيانة متكامل</span>
            <strong>جهازك في إيد أمينة.</strong>
            <small>فحص دقيق، سعر واضح، وتحديث مستمر لحالة الصيانة.</small>
        </div>
        <div class="hero-actions">
            <a class="btn btn-primary" href="{{ route('shop') }}">تسوّق الآن <span>←</span></a>
            <a class="btn btn-glass" href="{{ route('maintenance') }}">احجز صيانة</a>
        </div>
        <div class="trust-row"><span>✓ ضمان حقيقي</span><span>✓ دفع آمن</span><span>✓ دعم سريع</span></div>
    </div>
    <div class="hero-visual reveal">
        <div class="device-card">
            <span class="floating-tag top">جديد <b>2026</b></span>
            <div class="phone"><div class="phone-screen"><span class="dynamic-island"></span><x-brand-symbol class="hero-phone-logo"/><small>BashaMob</small></div></div>
            <span class="floating-tag bottom">ابتداءً من <b>18,999 ج.م</b></span>
        </div>
    </div>
</section>

<section class="container customer-pulse-grid" aria-label="مميزات باشا موب">
    <article><span class="pulse-icon"><svg viewBox="0 0 24 24"><path d="M12 3 4 6v5c0 5 3.2 8.4 8 10 4.8-1.6 8-5 8-10V6l-8-3Zm-3 9 2 2 4-5"/></svg></span><div><strong>ضمان حقيقي</strong><small>على الأجهزة والقطع الأصلية</small></div><b>موثوق</b></article>
    <article><span class="pulse-icon"><svg viewBox="0 0 24 24"><path d="M4 7h16v11H4V7Zm3-3h10v3M8 12h8"/></svg></span><div><strong>صيانة محترفة</strong><small>فحص دقيق وتحديث مستمر</small></div><b>متابعة</b></article>
    <article><span class="pulse-icon"><svg viewBox="0 0 24 24"><path d="M3 7h12v9H3V7Zm12 3h4l2 3v3h-6v-6ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span><div><strong>توصيل سريع</strong><small>وتغليف آمن لطلبك</small></div><b>سريع</b></article>
    <article><span class="pulse-icon"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6V3Zm3 4h6m-6 4h6m-4 6h2"/></svg></span><div><strong>طلبك واضح</strong><small>رقم متابعة لكل جهاز</small></div><b>24/7</b></article>
</section>
@endsection
