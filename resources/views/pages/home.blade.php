@extends('layouts.app')
@section('title', 'الرئيسية')
@php($activePage = 'home')
@section('content')
<section class="hero container">
    <div class="hero-copy reveal">
        <span class="eyebrow"><i></i> كل التكنولوجيا في مكان واحد</span>
        <h1>اختيارك الصح<br><span>يبدأ من باشا موب.</span></h1>
        <p>أحدث الموبايلات والكمبيوتر والإكسسوارات، مع مركز صيانة محترف يهتم بجهازك من أول فحص لحد الاستلام.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="{{ route('shop') }}">تسوّق الآن <span>←</span></a>
            <a class="btn btn-glass" href="{{ route('maintenance') }}">احجز صيانة</a>
        </div>
        <div class="trust-row"><span>✓ ضمان حقيقي</span><span>✓ دفع آمن</span><span>✓ دعم سريع</span></div>
    </div>
    <div class="hero-visual reveal">
        <div class="device-card">
            <span class="floating-tag top">جديد <b>2026</b></span>
            <div class="phone"><div class="phone-screen"><span class="dynamic-island"></span><b>B</b><small>BashaMob</small></div></div>
            <span class="floating-tag bottom">ابتداءً من <b>18,999 ج.م</b></span>
        </div>
    </div>
</section>

<section class="container section">
    <div class="section-head"><div><span class="eyebrow">اكتشف أقسامنا</span><h2>كل اللي محتاجه، أقرب لك</h2></div><a href="{{ route('shop') }}">عرض الكل ←</a></div>
    <div class="category-grid">
        <x-category-card title="الموبايلات" count="أحدث الموديلات" link="{{ route('shop') }}" icon="phone" />
        <x-category-card title="الكمبيوتر" count="أجهزة لكل استخدام" link="{{ route('computers') }}" icon="laptop" />
        <x-category-card title="الإكسسوارات" count="اختيارات أصلية" link="{{ route('shop') }}" icon="headphones" />
        <x-category-card title="مركز الصيانة" count="فنيون متخصصون" link="{{ route('maintenance') }}" icon="tools" />
    </div>
</section>

<section class="container section service-banner">
    <div><span class="eyebrow light">مركز صيانة متكامل</span><h2>جهازك في إيد أمينة.</h2><p>فحص دقيق، سعر واضح، وتحديث مستمر لحالة الصيانة.</p><a class="btn btn-light" href="{{ route('maintenance') }}">ابدأ طلب الصيانة</a></div>
    <div class="repair-orbit"><span>⚙</span><i></i><i></i><i></i></div>
</section>
@endsection
