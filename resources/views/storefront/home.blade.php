@extends('layouts.app')
@section('title', 'Salada Mix — Seu mix de estilos em um só lugar')
@section('content')
@php($demoVisuals = \App\Support\DemoMedia::enabled())
@php($tech = $categories->firstWhere('slug','tecnologia-e-informatica'))
@php($homeCat = $categories->firstWhere('slug','casa-e-decoracao'))
@php($beauty = $categories->firstWhere('slug','beleza-e-cuidados'))
@php($fashion = $categories->firstWhere('slug','moda-e-acessorios'))

<section class="sm-home-v3" data-home-revision="v4-desktop-fix" aria-labelledby="sm-home-v3-title">
    <div class="sm-home-v3-copy">
        <span class="sm-home-kicker">MAIS VARIEDADE, MAIS VOCÊ</span>
        <h1 id="sm-home-v3-title">Seu mix de estilos <em>em um só lugar.</em></h1>
        <p>Moda, tecnologia, casa, beleza, brinquedos e muito mais. Descubra produtos de diferentes lojas em uma experiência simples, organizada e feita para você explorar.</p>
        <div class="sm-home-v3-actions">
            <a class="sm-btn sm-btn-primary sm-btn-lg" href="{{ route('storefront.search') }}">Explorar produtos <span aria-hidden="true">→</span></a>
            <a class="sm-btn sm-btn-secondary sm-btn-lg" href="{{ route('seller.apply') }}">Quero vender</a>
        </div>
        <div class="sm-home-v3-benefits" aria-label="Diferenciais">
            <span><x-salada.icon name="grid" size="20" /><span><b>Diversas lojas</b><small>em um só lugar</small></span></span>
            <span><x-salada.icon name="shield" size="20" /><span><b>Conta protegida</b><small>acessos controlados</small></span></span>
            <span><x-salada.icon name="truck" size="20" /><span><b>Compra multi-lojas</b><small>logística por vendedor</small></span></span>
        </div>
    </div>

    <div class="sm-home-v3-visual" aria-label="Inspirações de departamentos">
        <a class="sm-home-v3-primary" href="{{ $fashion ? route('storefront.category',$fashion) : route('storefront.search') }}">
            @if($hero = \App\Support\DemoMedia::banner('hero'))
                <img src="{{ $hero }}" alt="Imagem ilustrativa de moda e estilo" fetchpriority="high" width="720" height="620" referrerpolicy="no-referrer" onerror="this.style.display='none'">
            @else
                <span class="sm-home-v3-fallback"><img src="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" alt="" width="150" height="150"></span>
            @endif
            <span class="sm-home-v3-primary-copy"><small>MODA</small><strong>Estilo para todos os momentos</strong><b aria-hidden="true">→</b></span>
        </a>

        <div class="sm-home-v3-side">
            <a class="sm-home-v3-mini sm-home-v3-tech" href="{{ $tech ? route('storefront.category',$tech) : route('storefront.search') }}">
                @if($img=\App\Support\DemoMedia::banner('technology'))<img src="{{ $img }}" alt="" width="480" height="260" referrerpolicy="no-referrer" onerror="this.style.display='none'">@endif
                <span><strong>Tecnologia</strong><small>Inovação para sua rotina</small></span><b aria-hidden="true">→</b>
            </a>
            <a class="sm-home-v3-mini sm-home-v3-house" href="{{ $homeCat ? route('storefront.category',$homeCat) : route('storefront.search') }}">
                @if($img=\App\Support\DemoMedia::banner('home'))<img src="{{ $img }}" alt="" width="480" height="260" referrerpolicy="no-referrer" onerror="this.style.display='none'">@endif
                <span><strong>Casa e Decoração</strong><small>Seu espaço, seu jeito</small></span><b aria-hidden="true">→</b>
            </a>
            <a class="sm-home-v3-mini sm-home-v3-beauty" href="{{ $beauty ? route('storefront.category',$beauty) : route('storefront.search') }}">
                @if($img=\App\Support\DemoMedia::banner('beauty'))<img src="{{ $img }}" alt="" width="480" height="260" referrerpolicy="no-referrer" onerror="this.style.display='none'">@endif
                <span><strong>Beleza e Cuidados</strong><small>Bem-estar para o dia a dia</small></span><b aria-hidden="true">→</b>
            </a>
        </div>
    </div>
</section>

<section id="departamentos" class="sm-home-section sm-category-section-v3" aria-labelledby="departamentos-titulo">
    <div class="sm-section-heading sm-section-heading-v2">
        <div><span class="sm-eyebrow">Encontre seu estilo</span><h2 id="departamentos-titulo">Nossos departamentos</h2><p>Navegue rapidamente pelo que combina com você.</p></div>
        <a href="{{ route('storefront.search') }}">Ver todos →</a>
    </div>
    <div class="sm-category-rail-v3">
        @forelse ($categories as $category)
            <a class="sm-category-item-v3" href="{{ route('storefront.category', $category) }}">
                <span class="sm-category-picture-v3">
                    @if($thumb = \App\Support\DemoMedia::category($category->slug))
                        <img src="{{ $thumb }}" alt="" loading="lazy" width="180" height="180" referrerpolicy="no-referrer" onerror="this.style.display='none'">
                    @else
                        <x-salada.icon name="grid" size="27" />
                    @endif
                </span>
                <strong>{{ $category->name }}</strong>
            </a>
        @empty
            <p class="sm-empty">Os departamentos estarão disponíveis em breve.</p>
        @endforelse
    </div>
</section>

<section id="ofertas" class="sm-home-section sm-featured-section sm-featured-v3" aria-labelledby="ofertas-titulo">
    <div class="sm-section-heading sm-section-heading-v2">
        <div><span class="sm-eyebrow">Produtos para descobrir</span><h2 id="ofertas-titulo">Destaques do seu mix</h2><p>Produtos publicados por lojas habilitadas no catálogo.</p></div>
        <a href="{{ route('storefront.search') }}">Ver todos os produtos →</a>
    </div>
    @if($demoVisuals)<p class="sm-demo-footnote">Conteúdo sintético de homologação. Sem descontos, avaliações ou promessas comerciais fictícias.</p>@endif
    <div class="sm-offer-grid sm-offer-grid-home sm-offer-grid-v3">
        @forelse ($offers as $offer)
            @include('storefront._offer-card', ['offer' => $offer])
        @empty
            <p class="sm-empty">Novos produtos aparecerão aqui depois da aprovação das lojas e das ofertas.</p>
        @endforelse
    </div>
</section>

<section class="sm-home-seller-banner" aria-labelledby="sm-seller-home-title">
    <div><span class="sm-eyebrow">Venda no Salada Mix</span><h2 id="sm-seller-home-title">Sua loja também pode fazer parte desse mix.</h2><p>Cadastre sua empresa, organize produtos e equipe e acompanhe a análise dentro da plataforma.</p></div>
    <a class="sm-btn sm-btn-primary sm-btn-lg" href="{{ route('seller.apply') }}">Quero vender no Salada Mix →</a>
</section>
@endsection
