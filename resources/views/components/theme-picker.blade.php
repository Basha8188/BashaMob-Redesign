<div class="theme-picker">
    <button class="theme-picker-toggle" type="button" aria-label="اختيار ألوان الواجهة" aria-expanded="false"><span></span></button>
    <div class="theme-palette" aria-label="أنظمة الألوان">
        @foreach(['ocean'=>'أزرق هادئ','royal'=>'أزرق وذهبي','forest'=>'أخضر طبيعي','plum'=>'برقوقي','slate'=>'رمادي مزرق'] as $key=>$label)
            <button type="button" data-palette="{{ $key }}" title="{{ $label }}"><i></i><span>{{ $label }}</span></button>
        @endforeach
    </div>
</div>
