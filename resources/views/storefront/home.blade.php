@extends('layouts.app')
@section('title', 'Salada Mix — Seu mix de estilos em um só lugar')
@section('content')
@php($demoVisuals = \App\Support\DemoMedia::enabled())
@if($demoVisuals)
    <div class="sm-demo-label sm-demo-label-v2" role="status"><strong>HOMOLOGAÇÃO VISUAL + FUNCIONAL</strong><span>Fotos e produtos DEMO. Frete e pagamento desta HML usam SANDBOX identificado.</span><a href="{{ route('storefront.demo') }}">Testar jornada completa →</a></div>
@endif

<section class="sm-home-v2" aria-labelledby="sm-home-v2-title">
    <div class="sm-home-v2-copy">
        <span class="sm-home-kicker">MAIS VARIEDADE, MAIS VOCÊ</span>
        <h1 id="sm-home-v2-title">Seu mix de estilos <em>em um só lugar.</em></h1>
        <p>Moda, tecnologia, casa, beleza, brinquedos e muito mais. Descubra produtos de diferentes lojas com uma experiência simples e organizada.</p>
        <div class="sm-home-v2-actions">
            <a class="sm-btn sm-btn-primary sm-btn-lg" href="{{ route('storefront.search') }}">Explorar produtos <span aria-hidden="true">→</span></a>
            <a class="sm-btn sm-btn-secondary sm-btn-lg" href="#departamentos">Ver departamentos</a>
        </div>
        <div class="sm-home-trust" aria-label="Diferenciais">
            <span><x-salada.icon name="grid" size="20" /><b>Muitas categorias</b><small>Um catálogo para diferentes estilos</small></span>
            <span><x-salada.icon name="shield" size="20" /><b>Conta protegida</b><small>Acesso e dados controlados</small></span>
            <span><x-salada.icon name="truck" size="20" /><b>Multi-lojas</b><small>Logística organizada por vendedor</small></span>
        </div>
    </div>

    <div class="sm-home-v2-mosaic" aria-label="Inspirações do Salada Mix">
        <div class="sm-home-feature-photo">
            @if($hero = \App\Support\DemoMedia::banner('hero'))
                <img src="{{ $hero }}" alt="Imagem ilustrativa de estilo e compras" fetchpriority="high" width="720" height="610" referrerpolicy="no-referrer">
            @else
                <img src="{{ asset('assets/salada/salada-mix-simbolo.svg') }}" alt="" width="190" height="190">
            @endif
            <span class="sm-hand-note">Mais variedade<br>para o seu dia a dia ♡</span>
        </div>
        @php($tech = $categories->firstWhere('slug','tecnologia-e-informatica'))
        @php($homeCat = $categories->firstWhere('slug','casa-e-decoracao'))
        @php($beauty = $categories->firstWhere('slug','beleza-e-cuidados'))
        <a class="sm-home-mini sm-home-mini-coral" href="{{ $tech ? route('storefront.category',$tech) : route('storefront.search') }}">
            @if($img=\App\Support\DemoMedia::banner('technology'))<img src="{{ $img }}" alt="" width="360" height="240" referrerpolicy="no-referrer">@endif
            <span><strong>Tecnologia</strong><small>Para facilitar sua rotina →</small></span>
        </a>
        <a class="sm-home-mini sm-home-mini-home" href="{{ $homeCat ? route('storefront.category',$homeCat) : route('storefront.search') }}">
            @if($img=\App\Support\DemoMedia::banner('home'))<img src="{{ $img }}" alt="" width="360" height="240" referrerpolicy="no-referrer">@endif
            <span><strong>Casa e Decoração</strong><small>Seu espaço, seu jeito →</small></span>
        </a>
        <a class="sm-home-mini sm-home-mini-beauty" href="{{ $beauty ? route('storefront.category',$beauty) : route('storefront.search') }}">
            @if($img=\App\Support\DemoMedia::banner('beauty'))<img src="{{ $img }}" alt="" width="360" height="240" referrerpolicy="no-referrer">@endif
            <span><strong>Beleza e Cuidados</strong><small>Encontre seu estilo →</small></span>
        </a>
    </div>
</section>

<section id="departamentos" class="sm-home-section sm-department-section" aria-labelledby="departamentos-titulo">
    <div class="sm-section-heading sm-section-heading-v2">
        <div><span class="sm-eyebrow">Encontre seu estilo</span><h2 id="departamentos-titulo">Explore os departamentos</h2><p>Atalhos rápidos para navegar pelo seu mix.</p></div>
        <a href="{{ route('storefront.search') }}">Ver todos os departamentos →</a>
    </div>
    <div class="sm-department-strip">
        @forelse ($categories as $category)
            <a class="sm-department-card" href="{{ route('storefront.category', $category) }}">
                <span class="sm-department-picture">
                    @if($thumb = \App\Support\DemoMedia::category($category->slug))
                        <img src="{{ $thumb }}" alt="" loading="lazy" width="180" height="180" referrerpolicy="no-referrer">
                    @else
                        <x-salada.icon name="grid" size="28" />
                    @endif
                </span>
                <span><strong>{{ $category->name }}</strong><small>Explorar →</small></span>
            </a>
        @empty
            <p class="sm-empty">Os departamentos estarão disponíveis em breve.</p>
        @endforelse
    </div>
</section>

<section id="ofertas" class="sm-home-section sm-featured-section" aria-labelledby="ofertas-titulo">
    <div class="sm-section-heading sm-section-heading-v2">
        <div><span class="sm-eyebrow">Produtos para descobrir</span><h2 id="ofertas-titulo">Destaques do seu mix</h2><p>Ofertas publicadas por lojas habilitadas no catálogo.</p></div>
        <a href="{{ route('storefront.search') }}">Ver catálogo completo →</a>
    </div>
    @if($demoVisuals)<p class="sm-demo-footnote">Conteúdo sintético de homologação. Sem descontos, avaliações ou promessas comerciais fictícias.</p>@endif
    <div class="sm-offer-grid sm-offer-grid-home">
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
