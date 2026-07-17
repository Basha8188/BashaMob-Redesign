@extends('layouts.app')
@section('title', 'المتجر')
@php($activePage = 'shop')
@section('content')
<x-page-hero eyebrow="متجر باشا موب" title="اختار جهازك الجديد" text="منتجات أصلية، أسعار واضحة، وضمان يطمنك." />
<section class="container section compact"><div class="filter-bar"><button class="active" data-product-filter="all">الكل</button><button data-product-filter="phones">موبايلات</button><button data-product-filter="accessories">إكسسوارات</button><button data-product-filter="offers">عروض</button><label><input type="search" data-product-search placeholder="ابحث عن منتج..."><span>⌕</span></label></div>
<div class="products-grid">
    <x-product-card name="iPhone 16 Pro" category="Apple" price="62,900" kind="phones" />
    <x-product-card name="Galaxy S25 Ultra" category="Samsung" price="54,500" accent="blue" kind="phones" :offer="true" />
    <x-product-card name="Xiaomi 15" category="Xiaomi" price="31,999" accent="orange" kind="phones" />
    <x-product-card name="AirPods Pro" category="Accessories" price="11,500" accent="pink" kind="accessories" />
</div></section>
<button class="customer-cart-trigger" type="button" data-cart-open><span>طلباتك</span><b data-cart-count>0</b></button>
<div class="customer-order-modal" data-order-modal aria-hidden="true">
    <button class="customer-order-backdrop" type="button" data-cart-close aria-label="إغلاق"></button>
    <section class="customer-order-card" role="dialog" aria-modal="true" aria-labelledby="customer-order-title">
        <header><span><small>طلب شراء جديد</small><strong id="customer-order-title">راجع طلبك وسجّل بياناتك</strong></span><button type="button" data-cart-close>×</button></header>
        <div class="customer-cart-items" data-cart-items></div>
        <form data-customer-order action="{{ route('customer.orders.store') }}" method="POST">@csrf
            <div class="form-row"><label>الاسم بالكامل<input name="name" required maxlength="120"></label><label>رقم الموبايل<input name="phone" required pattern="01[0-9]{9}" placeholder="01xxxxxxxxx"></label></div>
            <label>البريد الإلكتروني<input name="email" type="email" placeholder="name@example.com"></label>
            <button class="btn btn-primary" type="submit">تسجيل طلب الشراء</button>
            <p data-order-result role="status"></p>
        </form>
    </section>
</div>
@endsection
