@extends('layouts.app')
@section('title', 'Salada Mix — Seu mix. Seu estilo.')
@section('content')
@php($demoVisuals = \App\Support\DemoMedia::enabled())
@if($demoVisuals)
    <div class="sm-demo-label" role="status"><strong>SALADA MIX · DEMONSTRAÇÃO</strong><span>Produtos ilustrativos, preços e vendedores sintéticos. Sem compras reais.</span></div>
@endif
<section class="sm-editorial-hero" aria-labelledby="sm-hero-title">
    <div class="sm-editorial-copy">
        <span class="sm-eyebrow">SALADA MIX · SEU UNIVERSO DE POSSIBILIDADES</span>
        <h1 id="sm-hero-title">Tudo o que você ama, em um só lugar.</h1>
        <p>Beleza, moda, tecnologia, casa e muito mais. Um marketplace para descobrir produtos de todos os estilos.</p>
        <div class="sm-hero-ctas">
            <a class="sm-btn sm-btn-primary" href="{{ route('storefront.search') }}">Explorar produtos <span aria-hidden="true">→</span></a>
            <a class="sm-btn sm-btn-secondary" href="#departamentos">Ver departamentos</a>
        </div>
    </div>
    <div class="sm-editorial-media">
        @if($hero = \App\Support\DemoMedia::banner('hero'))
            <img src="{{ $hero }}" alt="Fotografia de moda ilustrativa da apresentação Salada Mix" fetchpriority="high" width="560" height="360" referrerpolicy="no-referrer">
        @else
            <img class="sm-brand-hero-mark" src="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" alt="" width="170" height="170">
        @endif
        <span class="sm-hero-sticker">Vários estilos.<br><strong>Um só mix.</strong></span>
    </div>
</section>
<section class="sm-market-highlights" aria-label="Navegação e informações da plataforma">
    <span><x-salada.icon name="grid" size="22" /> Muitos departamentos</span>
    <span><x-salada.icon name="search" size="22" /> Busque por produtos e lojas</span>
    <span><x-salada.icon name="shield" size="22" /> Empresas sujeitas a aprovação</span>
</section>
<section id="departamentos" aria-labelledby="departamentos-titulo">
    <div class="sm-section-heading"><div><span class="sm-eyebrow">Encontre seu estilo</span><h2 id="departamentos-titulo">Explore os departamentos</h2></div><a href="{{ route('storefront.search') }}">Ver todos →</a></div>
    <div class="sm-photo-category-grid">
        @forelse ($categories as $category)
            <a class="sm-photo-category" href="{{ route('storefront.category', $category) }}">
                <span class="sm-photo-category-visual">
                    @if($thumb = \App\Support\DemoMedia::category($category->slug))
                        <img src="{{ $thumb }}" alt="" loading="lazy" width="160" height="160" referrerpolicy="no-referrer">
                    @else
                        <x-salada.icon name="grid" size="26" />
                    @endif
                </span>
                <strong>{{ $category->name }}</strong>
            </a>
        @empty
            <p class="sm-empty">Os departamentos estarão disponíveis em breve.</p>
        @endforelse
    </div>
</section>
<section id="ofertas" aria-labelledby="ofertas-titulo">
    <div class="sm-section-heading"><div><span class="sm-eyebrow">Descubra o seu próximo achado</span><h2 id="ofertas-titulo">Produtos em destaque</h2></div><a href="{{ route('storefront.search') }}">Explorar catálogo →</a></div>
    @if($demoVisuals)<p class="sm-demo-footnote">Produtos ilustrativos de homologação · sem descontos, avaliações ou condições de frete simuladas.</p>@endif
    <div class="sm-offer-grid">
        @forelse ($offers as $offer)
            @include('storefront._offer-card', ['offer' => $offer])
        @empty
            <p class="sm-empty">Estamos credenciando empresas. As ofertas serão exibidas após aprovação e habilitação comercial.</p>
        @endforelse
    </div>
</section>
@if($demoVisuals)
<section class="sm-promo-grid" aria-label="Inspirações de departamento (imagens ilustrativas)">
    @foreach([['beauty','Beleza para sua rotina','beleza-e-cuidados'],['technology','Tecnologia para o dia a dia','tecnologia-e-informatica'],['home','Sua casa, seu jeito','casa-e-decoracao']] as [$photo, $label, $slug])
    @php($target = $categories->firstWhere('slug', $slug))
    <a class="sm-promo-tile" href="{{ $target ? route('storefront.category', $target) : route('storefront.search') }}">
        <img src="{{ \App\Support\DemoMedia::banner($photo) }}" alt="" loading="lazy" width="450" height="230" referrerpolicy="no-referrer">
        <span><strong>{{ $label }}</strong><small>Descubra produtos →</small></span>
    </a>
    @endforeach
</section>
@endif
@endsection
