@extends('layouts.app')
@section('title', 'Salada Mix — Tudo num só lugar')
@section('content')
<section class="sm-home-hero" aria-labelledby="sm-hero-title">
    <div>
        <span class="sm-eyebrow">O marketplace de todos os estilos</span>
        <h1 id="sm-hero-title">Seu mix. Seu estilo. Tudo num só lugar.</h1>
        <p>Beleza, moda, tecnologia, casa e muito mais. Estamos reunindo empresas e preparando uma experiência de compra para todos.</p>
        <div class="sm-hero-ctas">
            <a class="sm-btn sm-btn-primary" href="#departamentos">Explorar departamentos</a>
            <a class="sm-btn sm-btn-secondary" href="{{ route('seller.apply') }}">Quero vender</a>
        </div>
    </div>
    <div class="sm-hero-art" aria-hidden="true">
        <span class="sm-hero-circle"></span>
        <img class="sm-hero-logo" src="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" alt="">
        <span class="sm-spark one"></span><span class="sm-spark two"></span>
    </div>
</section>
<section id="departamentos" aria-labelledby="departamentos-titulo">
    <div class="sm-section-heading"><h2 id="departamentos-titulo">Explore os departamentos</h2><p>Um mundo de possibilidades no seu mix</p></div>
    <div class="sm-category-grid">
        @forelse ($categories as $category)
            <a class="sm-category-link" href="{{ route('storefront.category', $category) }}">{{ $category->name }}</a>
        @empty
            <p class="sm-empty">Os departamentos estarão disponíveis em breve.</p>
        @endforelse
    </div>
</section>
<section id="ofertas" aria-labelledby="ofertas-titulo">
    <div class="sm-section-heading"><h2 id="ofertas-titulo">Ofertas disponíveis</h2><p>Somente anúncios revisados e empresas habilitadas</p></div>
    <div class="sm-offer-grid">
        @forelse ($offers as $offer)
            @include('storefront._offer-card', ['offer' => $offer])
        @empty
            <p class="sm-empty">Estamos credenciando empresas. As ofertas serão exibidas após aprovação e habilitação comercial.</p>
        @endforelse
    </div>
</section>
@endsection
