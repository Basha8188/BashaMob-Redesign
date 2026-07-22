@extends('layouts.admin')
@section('title','جواز سفر الجهاز')
@section('content')
<div class="admin-page-head"><div><span class="eyebrow">Device Passport</span><h1>جواز سفر الجهاز</h1><p>ابحث بالـIMEI أو Serial أو العميل أو التذكرة أو الفاتورة، وشاهد تاريخ الجهاز من مكان واحد.</p></div></div>
@if(session('status'))<div class="reference-notice">{{ session('status') }}</div>@endif
<section class="admin-panel glass-panel passport-search-panel">
    <form method="get" class="passport-search"><input name="q" value="{{ request('q') }}" placeholder="IMEI، Serial، هاتف العميل، اسمه، رقم التذكرة أو الفاتورة"><button class="btn btn-primary">بحث شامل</button></form>
</section>
<div class="passport-layout">
    <section class="admin-panel glass-panel">
        <div class="panel-head"><div><h2>الأجهزة المسجلة</h2><p>{{ $devices->total() }} جواز جهاز</p></div></div>
        <div class="passport-list">
            @forelse($devices as $device)
            <a href="{{ route('admin.device-passports.show',$device) }}" class="passport-row"><span class="passport-device-icon">▣</span><span><strong>{{ trim($device->brand.' '.$device->model) ?: $device->type }}</strong><small>{{ $device->internal_code }} · {{ $device->imei_1 ?: ($device->serial_number ?: 'بدون رقم خارجي') }}</small></span><b>{{ $device->customer->name }}</b></a>
            @empty<p class="empty-admin">لا توجد أجهزة مطابقة.</p>@endforelse
        </div>
        {{ $devices->links() }}
    </section>
    @can('device_passports.create')
    <section class="admin-panel glass-panel"><div class="panel-head"><div><h2>إنشاء جواز جهاز</h2><p>سيتم إنشاء كود داخلي تلقائيًا.</p></div></div>
        <form method="post" action="{{ route('admin.device-passports.store') }}" class="passport-form">@csrf
            <label>المالك<select name="customer_id" required><option value="">اختر العميل</option>@foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }} — {{ $customer->phone }}</option>@endforeach</select></label>
            <div><label>نوع الجهاز<input name="type" required placeholder="هاتف / لابتوب"></label><label>الماركة<input name="brand"></label></div>
            <div><label>الموديل<input name="model"></label><label>اللون<input name="color"></label></div>
            <div><label>السعة<input name="storage"></label><label>IMEI 1<input name="imei_1"></label></div>
            <div><label>IMEI 2<input name="imei_2"></label><label>Serial Number<input name="serial_number"></label></div>
            <label>ملاحظات<textarea name="notes"></textarea></label>
            <button class="btn btn-primary">إنشاء جواز الجهاز</button>
        </form>
    </section>@endcan
</div>
@endsection
