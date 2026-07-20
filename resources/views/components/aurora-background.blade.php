@props(['variant' => 'storefront'])
<div {{ $attributes->class(['aurora-background', "aurora-background--{$variant}"]) }} data-aurora-background aria-hidden="true">
    <div class="aurora-background__layer aurora-background__base"></div>
    <div class="aurora-background__layer aurora-background__mesh"></div>
    <div class="aurora-background__layer aurora-background__ribbons"><i></i><i></i><i></i><i></i></div>
    <div class="aurora-background__layer aurora-background__fog"></div>
    <div class="aurora-background__layer aurora-background__rays"></div>
    <div class="aurora-background__layer aurora-background__bloom"></div>
    <div class="aurora-background__layer aurora-background__dust">
        @foreach ([[8,16,.28,31,-8],[19,72,.18,43,-21],[31,38,.3,37,-13],[43,84,.2,49,-29],[55,21,.26,35,-17],[64,62,.16,47,-7],[73,43,.3,39,-24],[84,78,.2,45,-15],[91,27,.24,33,-26],[13,49,.17,41,-11],[48,54,.2,50,-33],[78,11,.18,38,-19]] as [$x,$y,$opacity,$speed,$delay])
            <b style="--dust-x:{{ $x }}%;--dust-y:{{ $y }}%;--dust-opacity:{{ $opacity }};--dust-speed:{{ $speed }}s;--dust-delay:{{ $delay }}s"></b>
        @endforeach
    </div>
    <div class="aurora-background__layer aurora-background__noise"></div>
    <div class="aurora-background__layer aurora-background__vignette"></div>
</div>
