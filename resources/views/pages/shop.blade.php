@extends('layouts.app')
@section('title', 'المتجر')
@php($activePage = 'shop')
@section('content')
<x-page-hero eyebrow="متجر باشا موب" title="اختار جهازك الجديد" text="منتجات أصلية، أسعار واضحة، وضمان يطمنك." />
<section class="container section compact"><div class="filter-bar"><button class="active">الكل</button><button>موبايلات</button><button>إكسسوارات</button><button>عروض</button><label><input type="search" placeholder="ابحث عن منتج..."><span>⌕</span></label></div>
<div class="products-grid">
    <x-product-card name="iPhone 16 Pro" category="Apple" price="62,900" />
    <x-product-card name="Galaxy S25 Ultra" category="Samsung" price="54,500" accent="blue" />
    <x-product-card name="Xiaomi 15" category="Xiaomi" price="31,999" accent="orange" />
    <x-product-card name="AirPods Pro" category="Accessories" price="11,500" accent="pink" />
</div></section>
@endsection
