@props(['admin' => false])
<div class="theme-picker {{ $admin ? 'admin-palette-picker' : '' }}">
    <button class="theme-picker-toggle" type="button" aria-label="اختيار ألوان الواجهة" aria-expanded="false"><span></span></button>
    <div class="theme-palette" aria-label="أنظمة الألوان">
        @foreach(['liquid-cosmos'=>'Liquid Glass Cosmos','default-violet'=>'Basha Violet','lavender'=>'Lavender','plum'=>'Plum','forest-moss'=>'Forest Moss','cocoa-dust'=>'Cocoa Dust','twilight-blue'=>'Twilight Blue','moonstone'=>'Moonstone'] as $key=>$label)
            <button type="button" data-palette="{{ $key }}" aria-label="{{ $label }}" aria-pressed="false"><i></i><span aria-hidden="true"></span></button>
        @endforeach
    </div>
</div>
