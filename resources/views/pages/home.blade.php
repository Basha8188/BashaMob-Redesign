@extends('layouts.app')
@section('title', 'الرئيسية')
@section('body-class', 'customer-home-dashboard')
@php($activePage = 'home')
@section('content')
<div class="home-console-shell">
    <aside class="home-console-sidebar">
        <div class="home-profile"><x-user-avatar/><span><b>أهلًا بك</b><small>عميل BashaMob</small></span><i>•••</i></div>
        <a class="home-console-brand" href="{{ route('home') }}"><x-brand-symbol/><span><b>BashaMob</b><small>كل التكنولوجيا في مكان واحد</small></span></a>
        <nav>
            <span>القائمة الرئيسية</span>
            @foreach([['home','الرئيسية','dashboard'],['shop','الموبايلات والمتجر','store'],['computers','الكمبيوتر واللابتوب','products'],['maintenance','الصيانة وتسجيل الجهاز','repairs']] as $item)
                <a class="{{ $item[0] === 'home' ? 'active' : '' }}" href="{{ route($item[0]) }}"><x-admin-icon :name="$item[2]"/><b>{{ $item[1] }}</b></a>
            @endforeach
            <span>خدمات العملاء</span>
            <a href="{{ route('maintenance') }}#track"><x-admin-icon name="reports"/><b>متابعة طلب صيانة</b></a>
            <a href="{{ route('shop') }}"><x-admin-icon name="sales"/><b>العروض والأسعار</b></a>
            <a href="mailto:info@bashamob.local"><x-admin-icon name="settings"/><b>تواصل معنا</b></a>
        </nav>
        <div class="home-sidebar-help"><span>؟</span><div><b>تحتاج مساعدة؟</b><small>فريق الدعم متاح الآن</small></div></div>
        <div class="home-sidebar-status"><i></i> المتجر يعمل الآن</div>
    </aside>
    <div class="home-console-workspace">
        <header class="home-console-topbar">
            <form data-home-search><x-admin-icon name="reports"/><input type="search" placeholder="بحث سريع عن موبايل، كمبيوتر أو صيانة..."><kbd>Ctrl + K</kbd><button type="submit" aria-label="بحث">⌕</button></form>
            <div><span><b>مرحبًا بك في BashaMob</b><small>اختيارك الصح يبدأ من هنا</small></span><x-user-avatar/></div>
        </header>
        <div class="home-console-toolbar">
            <div class="home-tool-icons"><a href="tel:01012345678" title="اتصل بنا">⌕</a><a href="{{ route('shop') }}" title="المتجر">▣</a><a href="{{ route('maintenance') }}" title="الصيانة">⌁</a><a href="{{ route('computers') }}" title="الكمبيوتر">▤</a><a href="mailto:info@bashamob.local" title="البريد">✉</a><a href="{{ route('maintenance') }}#track" title="متابعة الطلب" class="tool-alert">♢<i>1</i></a></div>
            <div class="home-console-clock"><span>●</span><b>{{ now()->format('h:i:s A') }}</b><small>{{ now()->translatedFormat('l، j F Y') }}</small></div>
            <div class="home-console-title"><span>متجر ومركز صيانة BashaMob</span><x-brand-symbol/></div>
        </div>
        <main class="home-console-main">
            <div class="home-console-heading"><span><b>الرئيسية</b><small>نظرة سريعة على كل خدمات ومنتجات باشا موب</small></span><div><button>صغير</button><button class="active">متوسط</button><button>كبير</button></div></div>
            <section class="home-console-metrics">
                @foreach([
                    ['cyan','أحدث الموبايلات','اختيارات 2026','products'],['pink','طلبات الصيانة','استقبال فوري','repairs'],['violet','الكمبيوتر واللابتوب','أجهزة أصلية','inventory'],['gold','العروض الحالية','أسعار مميزة','sales'],
                    ['orange','ضمان حقيقي','على الأجهزة والقطع','settings'],['magenta','متابعة الطلب','برقم متابعة','reports'],['blue','توصيل سريع','وتغليف آمن','suppliers'],['amber','دعم العملاء','متاح الآن','customers']
                ] as $metric)
                    <article class="home-metric-{{ $metric[0] }}"><x-admin-icon :name="$metric[3]"/><span><small>{{ $metric[1] }}</small><strong>{{ $metric[2] }}</strong></span><i></i></article>
                @endforeach
            </section>
            <section class="home-today-panel">
                <div class="home-panel-tabs"><span><x-admin-icon name="dashboard"/><b>محتوى اليوم</b></span><div><button class="active">المميز</button><button>المتجر</button><button>الصيانة</button><button>الكمبيوتر</button></div></div>
                <div class="home-today-content"><span class="home-today-icon"><x-brand-symbol/></span><div><b>كل اللي محتاجه لجهازك في مكان واحد</b><p>أحدث الموبايلات والكمبيوتر والإكسسوارات الأصلية، مع مركز صيانة محترف ومتابعة واضحة لطلبك.</p></div><div class="home-today-actions"><a class="primary" href="{{ route('shop') }}">تسوّق الآن ←</a><a href="{{ route('maintenance') }}">سجّل جهازك</a></div></div>
            </section>
            <section class="home-console-notice"><x-admin-icon name="repairs"/><span><b>جهازك في إيد أمينة</b><small>فحص دقيق، سعر واضح، وتحديث مستمر لحالة الصيانة حتى الاستلام.</small></span><a href="{{ route('maintenance') }}">احجز صيانة</a></section>
            <section class="home-services-panel">
                <div class="home-services-head"><span><x-admin-icon name="store"/><b>اختيارات BashaMob</b></span><small>اختر القسم المناسب وابدأ الآن</small></div>
                <div class="home-service-cards">
                    @foreach([['shop','الموبايلات','أحدث الأجهزة','products','2'],['computers','الكمبيوتر','للعمل والألعاب','inventory','1'],['shop','الإكسسوارات','اختيارات أصلية','purchases','3'],['maintenance','مركز الصيانة','فنيون متخصصون','repairs','4']] as $card)
                        <a href="{{ route($card[0]) }}"><x-admin-icon :name="$card[3]"/><span><b>{{ $card[1] }}</b><small>{{ $card[2] }}</small></span><i>0{{ $card[4] }}</i></a>
                    @endforeach
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
