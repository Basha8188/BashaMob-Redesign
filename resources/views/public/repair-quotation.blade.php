<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>عرض سعر {{ $quotation->quotation_number }} | BashaMob</title>
    <x-theme-bootstrap />
    @vite(['resources/css/app.css','resources/css/violet.css','resources/js/theme-manager.js'])
</head>
<body class="storefront-body public-portal">
<x-aurora-background/>
<x-theme-picker/>
<main class="public-card glass-panel">
    <header><strong class="luminous-brand">Basha<span>Mob</span></strong><small>موافقة رقمية آمنة</small></header>
    @if(session('status'))<div class="reference-notice">{{ session('status') }}</div>@endif
    <h1>عرض سعر الصيانة</h1>
    <p>{{ $quotation->quotation_number }} · {{ $quotation->ticket->ticket_number }}</p>
    <dl class="public-quote-data">
        <dt>الجهاز</dt><dd>{{ data_get($quotation->snapshot,'device.brand') }} {{ data_get($quotation->snapshot,'device.model') }}</dd>
        <dt>العطل</dt><dd>{{ $quotation->fault_description }}</dd>
        <dt>المدة المتوقعة</dt><dd>{{ $quotation->estimated_days ?: '—' }} يوم</dd>
        <dt>الإجمالي</dt><dd><strong>{{ number_format((float)$quotation->total,2) }} ج.م</strong></dd>
        <dt>المقدم</dt><dd>{{ number_format((float)$quotation->required_deposit,2) }} ج.م</dd>
    </dl>
    <div class="public-quote-items">
        @foreach($quotation->items as $item)<div><span>{{ $item->description }} × {{ $item->quantity }}</span><b>{{ $item->total }} ج.م</b></div>@endforeach
    </div>
    <p class="public-warranty">{{ $quotation->warranty_terms }}</p>
    @if($quotation->status==='pending_customer')
        <form method="post" action="{{ route('repair-quotations.public.respond',$token) }}">
            @csrf
            <label>رقم هاتف صاحب الطلب<input name="phone" required autocomplete="tel"></label>
            <label>ملاحظة اختيارية<textarea name="note"></textarea></label>
            <div class="public-decisions">
                <button name="decision" value="accept" class="btn btn-primary">قبول عرض السعر</button>
                <button name="decision" value="reject" class="btn btn-glass">رفض العرض</button>
                <button name="decision" value="contact" class="btn btn-glass">طلب التواصل</button>
            </div>
        </form>
    @else
        <div class="reference-notice">تم تسجيل القرار النهائي: {{ $quotation->status }}</div>
    @endif
</main>
</body>
</html>
