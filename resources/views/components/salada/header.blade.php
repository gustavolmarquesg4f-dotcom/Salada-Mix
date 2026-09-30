@php($shoppingCount = auth()->check() ? (int) \Illuminate\Support\Facades\DB::table('cart_items')->where('user_id', auth()->id())->sum('quantity') : 0)
<div class="sm-topbar sm-topbar-v2">
    <div class="sm-container sm-topbar-v2-inner">
        <span><x-salada.icon name="shield" size="17" /><strong>Experiência protegida</strong><small>Sessão e dados com acesso controlado</small></span>
        <span><x-salada.icon name="truck" size="18" /><strong>Frete por vendedor</strong><small>Em homologação nesta versão</small></span>
        <span><x-salada.icon name="grid" size="17" /><strong>Um mix de categorias</strong><small>Produtos de diferentes lojas</small></span>
    </div>
</div>
<header class="sm-header sm-header-v2">
    <div class="sm-container sm-head-main sm-head-main-v2">
        <details class="sm-mobile-details sm-mobile-menu-v2">
            <summary aria-label="Abrir menu principal"><x-salada.icon name="menu" size="22" /></summary>
            <nav class="sm-mobile-panel" aria-label="Navegação móvel">
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('storefront.search') }}">Explorar produtos</a>
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('storefront.category', $navCategory) }}">{{ $navCategory->name }}</a>
                @endforeach
                <a href="{{ route('seller.apply') }}">Quero vender</a>
                @auth
                    <a href="{{ route('buyer.account') }}">Minha conta</a>
                    <a href="{{ route('buyer.cart.page') }}">Minha sacola</a>
                    <a href="{{ route('buyer.wishlist.page') }}">Favoritos</a>
                    @can('review-sellers')
                        <a href="{{ route('admin.manage') }}">Administração</a>
                    @endcan
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                    <a href="{{ route('register') }}">Criar conta</a>
                @endauth
            </nav>
        </details>

        <a href="{{ route('home') }}" class="sm-brand sm-brand-v2" aria-label="Salada Mix — página inicial">
            <img src="{{ asset('assets/salada/salada-mix-logo.svg') }}" alt="Salada Mix" width="194" height="40">
        </a>

        <form class="sm-search sm-search-v2" method="get" action="{{ route('storefront.search') }}" role="search">
            <x-salada.icon name="search" size="20" />
            <label class="sr-only" for="sm-global-search">Buscar produtos ou lojas</label>
            <input id="sm-global-search" name="q" type="search"
                   value="{{ request()->routeIs('storefront.search') ? request()->query('q', '') : '' }}"
                   maxlength="100" placeholder="O que você está procurando hoje?">
            <span class="sm-search-category">Todas as categorias</span>
            <button type="submit" aria-label="Buscar produtos"><x-salada.icon name="search" size="20" /><span>Buscar</span></button>
        </form>

        <nav class="sm-actions sm-actions-v2" aria-label="Acesso rápido">
            @auth
                <a class="sm-action sm-account-shortcut" href="{{ route('buyer.account') }}">
                    <x-salada.icon name="user" size="23" />
                    <span class="sm-action-copy"><strong>Minha conta</strong><small>Olá, {{ \Illuminate\Support\Str::limit(auth()->user()->name, 11) }}</small></span>
                </a>
                <a class="sm-action sm-icon-action" href="{{ route('buyer.wishlist.page') }}" aria-label="Meus favoritos" title="Favoritos"><span aria-hidden="true">♡</span><small>Favoritos</small></a>
                <a class="sm-action sm-cart-action sm-icon-action" href="{{ route('buyer.cart.page') }}" aria-label="Minha sacola com {{ $shoppingCount }} itens">
                    <x-salada.icon name="cart" size="25" />
                    @if($shoppingCount > 0)<span class="sm-cart-count" aria-hidden="true">{{ min($shoppingCount, 99) }}</span>@endif
                    <small>Sacola</small>
                </a>
            @else
                <a class="sm-action sm-account-shortcut" href="{{ route('login') }}">
                    <x-salada.icon name="user" size="23" />
                    <span class="sm-action-copy"><strong>Entrar</strong><small>Minha conta</small></span>
                </a>
                <a class="sm-action sm-icon-action" href="{{ route('login') }}" aria-label="Entre para ver favoritos"><span aria-hidden="true">♡</span><small>Favoritos</small></a>
                <a class="sm-action sm-icon-action" href="{{ route('login') }}" aria-label="Entre para acessar sua sacola"><x-salada.icon name="cart" size="25" /><small>Sacola</small></a>
            @endauth
            <a class="sm-seller-cta" href="{{ route('seller.apply') }}">Quero vender</a>
        </nav>
    </div>
</header>
<div class="sm-nav sm-nav-v2">
    <nav class="sm-container sm-nav-inner sm-nav-inner-v2" aria-label="Departamentos">
        <a class="sm-all-departments" href="{{ route('storefront.search') }}"><x-salada.icon name="menu" size="18" /> Departamentos</a>
        @foreach ($navCategories as $navCategory)
            <a href="{{ route('storefront.category', $navCategory) }}">{{ $navCategory->name }}</a>
        @endforeach
        <span class="sm-nav-spacer"></span>
        @can('review-sellers')<a class="sm-admin-nav-link" href="{{ route('admin.manage') }}">Admin</a>@endcan
    </nav>
</div>

<nav class="sm-mobile-dock" aria-label="Atalhos móveis">
    <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif><span aria-hidden="true">⌂</span><small>Início</small></a>
    <a href="{{ route('storefront.search') }}" @if(request()->routeIs('storefront.search','storefront.category')) aria-current="page" @endif><x-salada.icon name="search" size="20" /><small>Buscar</small></a>
    @auth
        <a href="{{ route('buyer.wishlist.page') }}" @if(request()->routeIs('buyer.wishlist.page')) aria-current="page" @endif><span aria-hidden="true">♡</span><small>Favoritos</small></a>
        <a class="sm-mobile-cart" href="{{ route('buyer.cart.page') }}" @if(request()->routeIs('buyer.cart.page')) aria-current="page" @endif><x-salada.icon name="cart" size="20" />@if($shoppingCount > 0)<b>{{ min($shoppingCount,99) }}</b>@endif<small>Sacola</small></a>
        <a href="{{ route('buyer.account') }}" @if(request()->routeIs('buyer.account')) aria-current="page" @endif><x-salada.icon name="user" size="20" /><small>Conta</small></a>
    @else
        <a href="{{ route('login') }}"><span aria-hidden="true">♡</span><small>Favoritos</small></a>
        <a href="{{ route('login') }}"><x-salada.icon name="cart" size="20" /><small>Sacola</small></a>
        <a href="{{ route('login') }}"><x-salada.icon name="user" size="20" /><small>Entrar</small></a>
    @endauth
</nav>
