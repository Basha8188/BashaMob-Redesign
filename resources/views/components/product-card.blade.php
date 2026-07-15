@props(['name', 'category', 'price', 'accent' => 'violet', 'kind' => 'phones', 'offer' => false])
<article class="product-card reveal" data-product-card data-kind="{{ $kind }}" data-offer="{{ $offer ? '1' : '0' }}" data-product-name="{{ $name }} {{ $category }}">
    <div class="product-art {{ $accent }}"><span class="mini-phone"><i></i><x-brand-symbol class="mini-product-logo"/></span><em>متاح</em></div>
    <div class="product-info"><small>{{ $category }}</small><h3>{{ $name }}</h3><div><strong>{{ $price }} <small>ج.م</small></strong><button type="button" data-add-product aria-label="إضافة للسلة">+</button></div></div>
</article>
