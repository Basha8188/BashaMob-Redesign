@props(['admin' => false])
<div class="theme-picker {{ $admin ? 'admin-palette-picker' : '' }}">
    <button class="theme-picker-toggle" type="button" aria-label="اختيار ألوان الواجهة" aria-expanded="false"><span></span></button>
    <div class="theme-palette" aria-label="أنظمة الألوان">
        @foreach(['violet'=>'بنفسجي عميق','lavender'=>'لافندر هادئ','plum'=>'برقوقي فاخر'] as $key=>$label)
            <button type="button" data-palette="{{ $key }}" title="{{ $label }}"><i></i><span>{{ $label }}</span></button>
        @endforeach
    </div>
</div>
