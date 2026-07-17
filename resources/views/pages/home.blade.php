@extends('layouts.app')
@section('title', 'الرئيسية')
@section('body-class', 'customer-home-dashboard reference-home')
@php($activePage = 'home')
@section('content')
<div class="ref-dashboard">
    <header class="ref-windowbar">
        <div class="ref-profile"><x-user-avatar/><span><b>مدير النظام</b><small>مرحبًا بك</small></span></div>
        <form class="ref-search" data-home-search>
            <button type="submit" aria-label="بحث"><x-admin-icon name="reports"/></button>
            <input type="search" placeholder="بحث سريع...">
            <kbd>Ctrl + K</kbd>
        </form>
        <div class="ref-window-actions"><i>−</i><i>□</i><i>×</i></div>
    </header>

    <aside class="ref-sidebar">
        <nav>
            <a class="active" href="{{ route('home') }}"><x-admin-icon name="dashboard"/><b>الرئيسية</b></a>
            <a href="{{ route('shop') }}"><x-admin-icon name="products"/><b>الموبايلات والمتجر</b></a>
            <a href="{{ route('computers') }}"><x-admin-icon name="inventory"/><b>الكمبيوتر واللابتوب</b></a>
            <a href="{{ route('maintenance') }}"><x-admin-icon name="repairs"/><b>الصيانة وتسجيل الجهاز</b></a>
            <a href="{{ route('maintenance') }}#track"><x-admin-icon name="reports"/><b>متابعة طلب صيانة</b></a>
            <a href="{{ route('shop') }}"><x-admin-icon name="sales"/><b>العروض والأسعار</b></a>
            <a href="mailto:info@bashamob.local"><x-admin-icon name="settings"/><b>تواصل معنا</b></a>
        </nav>
        <div class="ref-side-bottom">
            <a href="{{ route('maintenance') }}"><x-admin-icon name="repairs"/><b>احجز صيانة</b></a>
            <a href="{{ route('shop') }}"><x-admin-icon name="sales"/><b>تسوّق الآن</b></a>
            <span><i></i> المتجر متاح الآن</span>
        </div>
    </aside>

    <section class="ref-workspace">
        <div class="ref-toolbar">
            <div class="ref-round-tools">
                <a href="tel:01012345678" title="اتصل بنا">⌕</a>
                <a href="{{ route('shop') }}" title="المتجر"><x-admin-icon name="store"/></a>
                <a href="{{ route('computers') }}" title="الكمبيوتر"><x-admin-icon name="inventory"/></a>
                <a href="{{ route('maintenance') }}" title="الصيانة"><x-admin-icon name="repairs"/></a>
                <a class="has-alert" href="{{ route('maintenance') }}#track" title="متابعة الطلب"><x-admin-icon name="reports"/><em>1</em></a>
                <a href="mailto:info@bashamob.local" title="البريد">✉</a>
            </div>
            <div class="ref-date"><b>{{ now()->format('h:i:s A') }}</b><span></span><small>{{ now()->translatedFormat('l، j F Y') }}</small></div>
            <div class="ref-system-title"><b>متجر ومركز صيانة BashaMob</b><x-brand-symbol/></div>
        </div>

        <main class="ref-main">
            <div class="ref-main-heading">
                <div class="ref-sizes"><button>صغير</button><button class="active">متوسط</button><button>كبير</button><i>⌘</i><i>◎</i></div>
                <span><b>الرئيسية</b><small>نظرة عامة على خدمات باشا موب</small></span>
            </div>

            <section class="ref-metrics">
                @foreach([
                    ['cyan','أحدث الموبايلات','اختيارات 2026','products'],
                    ['pink','طلبات الصيانة','استقبال فوري','repairs'],
                    ['violet','الكمبيوتر واللابتوب','أجهزة أصلية','inventory'],
                    ['gold','العروض الحالية','أسعار مميزة','sales'],
                    ['orange','ضمان حقيقي','على الأجهزة والقطع','settings'],
                    ['magenta','متابعة الطلب','برقم متابعة','reports'],
                    ['blue','توصيل سريع','وتغليف آمن','suppliers'],
                    ['amber','دعم العملاء','متاح الآن','customers']
                ] as $metric)
                    <article class="ref-metric ref-{{ $metric[0] }}"><span><small>{{ $metric[1] }}</small><strong>{{ $metric[2] }}</strong></span><x-admin-icon :name="$metric[3]"/></article>
                @endforeach
            </section>

            <section class="ref-glass-panel ref-daily">
                <div class="ref-panel-title"><span><x-admin-icon name="dashboard"/><b>محتوى اليوم</b></span><div><button class="active">المميز</button><button>المتجر</button><button>الصيانة</button><button>الكمبيوتر</button></div></div>
                <div class="ref-daily-body"><span class="ref-note-icon">↗</span><p><b>كل اللي محتاجه لجهازك في مكان واحد</b><small>موبايلات وكمبيوتر وإكسسوارات أصلية، مع صيانة محترفة ومتابعة واضحة.</small></p><div><a class="gold" href="{{ route('shop') }}">تسوّق الآن</a><a href="{{ route('maintenance') }}">سجّل جهازك</a></div></div>
            </section>

            <section class="ref-glass-panel ref-alert-line"><span>↗</span><p><b>جهازك في إيد أمينة</b><small>فحص دقيق وسعر واضح وتحديث مستمر لحالة الصيانة حتى الاستلام.</small></p><a href="{{ route('maintenance') }}">احجز صيانة</a></section>

            <section class="ref-glass-panel ref-delivery">
                <div class="ref-section-head"><span><x-admin-icon name="sales"/><b>خدمات BashaMob</b></span><small>اختر الخدمة المناسبة وابدأ الآن</small></div>
                <div class="ref-mini-cards">
                    @foreach([['shop','الموبايلات','أحدث الأجهزة','products','2'],['computers','الكمبيوتر','للعمل والألعاب','inventory','1'],['shop','الإكسسوارات','اختيارات أصلية','purchases','3'],['maintenance','مركز الصيانة','فنيون متخصصون','repairs','4']] as $card)
                        <a class="mini-{{ $card[4] }}" href="{{ route($card[0]) }}"><i>0{{ $card[4] }}</i><span><b>{{ $card[1] }}</b><small>{{ $card[2] }}</small></span><x-admin-icon :name="$card[3]"/></a>
                    @endforeach
                </div>
            </section>
        </main>
    </section>
</div>
@endsection
