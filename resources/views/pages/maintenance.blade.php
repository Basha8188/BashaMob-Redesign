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
    <div class="delivery-cinema" aria-hidden="true">
        <div class="delivery-hud"><span><x-brand-symbol/><b>BashaMob Express</b></span><div class="delivery-progress"><i></i></div><small>استلام <b>•</b> تحميل <b>•</b> توصيل</small></div>
        <svg class="delivery-movie" viewBox="0 0 960 420" role="presentation">
            <defs>
                <linearGradient id="cinemaSky" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#020817"/><stop offset=".55" stop-color="#071b3d"/><stop offset="1" stop-color="#092b51"/></linearGradient>
                <linearGradient id="cinemaRoad" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#0b1a31"/><stop offset="1" stop-color="#020713"/></linearGradient>
                <linearGradient id="truckPaint" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#31b8f4"/><stop offset=".48" stop-color="#126fe2"/><stop offset="1" stop-color="#153f9d"/></linearGradient>
                <linearGradient id="truckGlass" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#d9f8ff" stop-opacity=".94"/><stop offset="1" stop-color="#4ab8ed" stop-opacity=".44"/></linearGradient>
                <linearGradient id="hubGlass" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#1a5592" stop-opacity=".86"/><stop offset="1" stop-color="#071b3c" stop-opacity=".92"/></linearGradient>
                <radialGradient id="lampGlow"><stop stop-color="#a9f8ff"/><stop offset=".25" stop-color="#35d8ed" stop-opacity=".8"/><stop offset="1" stop-color="#35d8ed" stop-opacity="0"/></radialGradient>
                <pattern id="roadMarks" width="125" height="20" patternUnits="userSpaceOnUse"><rect width="70" height="4" y="8" rx="2" fill="#9ec7e8" fill-opacity=".38"/></pattern>
                <filter id="cinemaGlow" x="-100%" y="-100%" width="300%" height="300%"><feGaussianBlur stdDeviation="7" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                <filter id="cinemaShadow" x="-30%" y="-50%" width="160%" height="200%"><feDropShadow dx="0" dy="12" stdDeviation="10" flood-color="#000" flood-opacity=".55"/></filter>
            </defs>
            <rect width="960" height="420" rx="26" fill="url(#cinemaSky)"/>
            <circle class="cinema-moon-halo" cx="128" cy="75" r="76" fill="url(#lampGlow)" opacity=".16"/><circle cx="128" cy="75" r="25" fill="#bfeeff" opacity=".82"/>
            <g class="cinema-stars" fill="#d8f5ff"><circle cx="62" cy="44" r="1.2"/><circle cx="242" cy="66" r="1"/><circle cx="328" cy="35" r="1.3"/><circle cx="440" cy="78" r=".9"/><circle cx="545" cy="44" r="1.1"/><circle cx="665" cy="86" r="1.3"/><circle cx="782" cy="36" r="1"/><circle cx="896" cy="73" r="1.2"/></g>
            <g class="cinema-city" fill="#09264a"><path d="M0 210h48v-65h54v65h31v-92h66v92h42v-51h48v51h56v-111h68v111h38v-75h56v75h51v-96h72v96h34v-58h49v58h45v-84h69v84h33v-47h70v47h80v77H0Z"/></g>
            <g class="cinema-windows" fill="#39c9f1" opacity=".24"><path d="M58 160h9v9h-9zm19 0h9v9h-9zm78-27h10v10h-10zm22 0h10v10h-10zm185-17h11v10h-11zm23 0h11v10h-11zm189 14h10v10h-10zm23 0h10v10h-10zm191 4h10v10h-10zm22 0h10v10h-10z"/></g>
            <path d="M0 266 960 248v172H0Z" fill="url(#cinemaRoad)"/><path class="road-stream" d="M0 332h960" stroke="url(#roadMarks)" stroke-width="18"/><path d="M0 274 960 257" stroke="#3fa7de" stroke-opacity=".18" stroke-width="3"/>
            <g class="cinema-hub" filter="url(#cinemaShadow)"><path d="M38 150h188v137H38Z" fill="url(#hubGlass)" stroke="#4cc9f2" stroke-opacity=".4"/><path d="m26 150 105-52 108 52Z" fill="#0d477e" stroke="#58d5f3" stroke-opacity=".48"/><rect x="63" y="180" width="58" height="107" rx="5" fill="#071a35" stroke="#3ba4de" stroke-opacity=".5"/><rect x="143" y="178" width="58" height="48" rx="5" fill="#41c7ee" fill-opacity=".15" stroke="#65dbf4" stroke-opacity=".42"/><g class="hub-sign" filter="url(#cinemaGlow)"><rect x="70" y="125" width="126" height="36" rx="12" fill="#0c2850" stroke="#53d8f1" stroke-opacity=".7"/><text x="133" y="149" text-anchor="middle" fill="#e9fbff" font-size="15" font-weight="900">BashaMob</text></g><g class="hub-scanner"><circle cx="92" cy="232" r="48" fill="url(#lampGlow)" opacity="0"/><path d="M75 238h34" stroke="#62e6e7" stroke-width="3" stroke-linecap="round"/></g></g>
            <g class="cinema-dispatcher" filter="url(#cinemaShadow)"><circle cx="825" cy="157" r="20" fill="#d8a078"/><path d="M805 179q20-14 40 0l14 79h-68Z" fill="#1766b5"/><path d="m808 202-39 20" class="dispatcher-arm" stroke="#d8a078" stroke-width="13" stroke-linecap="round"/><path d="m807 256-15 56m51-56 18 56" stroke="#142b4d" stroke-width="15" stroke-linecap="round"/><path d="M803 184h44l-5 20h-34Z" fill="#38bfe9"/><circle cx="818" cy="154" r="2" fill="#172438"/><path d="M806 142q18-18 36 1" fill="none" stroke="#10233d" stroke-width="10" stroke-linecap="round"/></g>
            <g class="cinema-switch"><circle class="switch-aura" cx="755" cy="218" r="62" fill="url(#lampGlow)" opacity="0"/><rect x="733" y="195" width="42" height="51" rx="13" fill="#0a1b36" stroke="#476e9c"/><circle cx="754" cy="219" r="11" fill="#375371"/><circle class="switch-core" cx="754" cy="219" r="6" fill="#68839b"/></g>
            <g class="cinema-parcel" filter="url(#cinemaShadow)"><rect x="657" y="170" width="104" height="70" rx="14" fill="#0a2a52" stroke="#4bd8ee" stroke-opacity=".8"/><path d="M657 191h104" stroke="#4bd8ee" stroke-opacity=".28"/><text x="709" y="187" text-anchor="middle" fill="#6de5f2" font-size="9" font-weight="800">طلب صيانة</text><text data-launch-number x="709" y="214" text-anchor="middle" fill="#fff" font-size="10" font-weight="800"></text><circle cx="744" cy="226" r="8" fill="#29c89c"/><path d="m740 226 3 3 6-7" fill="none" stroke="#fff" stroke-width="2"/></g>
            <g class="cinema-truck" filter="url(#cinemaShadow)"><ellipse cx="115" cy="94" rx="118" ry="13" fill="#000" opacity=".35"/><path d="M87 7h161q14 0 14 14v61H87Z" fill="url(#truckPaint)"/><path d="M12 36h75v46H0V61l12-25Z" fill="url(#truckPaint)"/><path d="M22 41h35l20 28H14Z" fill="url(#truckGlass)" stroke="#91e7f9" stroke-opacity=".7"/><path d="M99 21h147M105 62h126" stroke="#d7f6ff" stroke-opacity=".22" stroke-width="3"/><rect x="125" y="30" width="91" height="25" rx="9" fill="#072b60" fill-opacity=".55"/><text x="170" y="47" text-anchor="middle" fill="#fff" font-size="14" font-weight="900">BashaMob</text><path d="M247 56h16v15h-16" fill="#78e7fb" class="truck-tail-light"/><path d="M0 62h-10v12H2" fill="#f5dc7c" class="truck-headlight"/><path class="headlight-beam" d="m-8 66-100-27v57Z" fill="#a4edff" opacity=".18"/><g class="truck-wheel"><circle cx="48" cy="84" r="20" fill="#030812" stroke="#4c729d" stroke-width="4"/><circle cx="48" cy="84" r="8" fill="#7fdff3"/><path d="M48 68v32M32 84h32M37 73l22 22M59 73 37 95" stroke="#153d61" stroke-width="3"/></g><g class="truck-wheel"><circle cx="210" cy="84" r="20" fill="#030812" stroke="#4c729d" stroke-width="4"/><circle cx="210" cy="84" r="8" fill="#7fdff3"/><path d="M210 68v32m-16-16h32m-27-11 22 22m0-22-22 22" stroke="#153d61" stroke-width="3"/></g></g>
            <g class="cinema-arrival" opacity="0"><circle cx="132" cy="81" r="46" fill="url(#lampGlow)"/><circle cx="132" cy="81" r="28" fill="#1bc596"/><path d="m118 81 10 10 21-25" fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></g>
            <g class="cinema-speed-lines" stroke="#7adcf4" stroke-linecap="round" opacity="0"><path d="M430 275h110M384 300h83M508 322h145"/><path d="M630 255h76M568 345h120"/></g>
        </svg>
        <div class="cinema-vignette"></div>
    </div>
    <strong data-delivery-status aria-live="polite">بنفتح خط الاستقبال لطلبك...</strong><small>استلام سريع، نقل آمن، ومتابعة دقيقة من BashaMob</small>
</div>
@endsection
