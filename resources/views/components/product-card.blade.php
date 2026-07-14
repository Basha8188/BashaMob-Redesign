@props(['name', 'category', 'price', 'accent' => 'violet'])
<article class="product-card reveal">
    <div class="product-art {{ $accent }}"><span class="mini-phone"><i></i><b>B</b></span><em>متاح</em></div>
    <div class="product-info"><small>{{ $category }}</small><h3>{{ $name }}</h3><div><strong>{{ $price }} <small>ج.م</small></strong><button aria-label="إضافة للسلة">+</button></div></div>
</article>
