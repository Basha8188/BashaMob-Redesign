@extends('layouts.app')
@section('title', 'الكمبيوتر')
@php($activePage = 'computers')
@section('content')
<x-page-hero eyebrow="عالم الكمبيوتر" title="قوة تناسب طموحك" text="لابتوبات وأجهزة مكتبية مختارة للشغل، الدراسة، والجيمينج." />
<section class="container section compact">
    <div class="feature-strip"><div><b>للدراسة</b><span>خفيف وعملي</span></div><div><b>للشغل</b><span>أداء يعتمد عليه</span></div><div><b>للألعاب</b><span>أقصى قوة</span></div></div>
    <div class="products-grid three"><x-product-card name="MacBook Air M4" category="Apple" price="58,900" kind="computers" /><x-product-card name="ROG Zephyrus" category="ASUS" price="74,500" accent="blue" kind="computers" /><x-product-card name="ThinkPad X1" category="Lenovo" price="49,999" accent="orange" kind="computers" /></div>
</section>
@endsection
