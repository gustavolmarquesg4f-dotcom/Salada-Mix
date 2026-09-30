<div class="sm-topbar">
    <div class="sm-container sm-topbar-inner">
        <span>Tudo num só lugar. Um mundo de possibilidades no seu mix.</span>
        <div class="sm-topbar-right"><a href="{{ route('seller.apply') }}">Venda no Salada Mix</a><span>Marketplace em preparação</span></div>
    </div>
</div>
<header class="sm-header">
    <div class="sm-container sm-head-main">
        <details class="sm-mobile-details">
            <summary aria-label="Abrir menu principal"><x-salada.icon name="menu" size="22" /></summary>
            <nav class="sm-mobile-panel" aria-label="Navegação móvel">
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('storefront.search') }}">Todos os produtos</a>
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('storefront.category', $navCategory) }}">{{ $navCategory->name }}</a>
                @endforeach
                <a href="{{ route('seller.apply') }}">Quero vender</a>
                @auth
                    <a href="{{ route('buyer.account') }}">Minha conta</a>
                    <a href="{{ route('buyer.cart.page') }}">Minha sacola</a>
                    <a href="{{ route('buyer.wishlist.page') }}">Favoritos</a>
                    @can('review-sellers')
                        <a href="{{ route('admin.sellers.index') }}">Empresas</a>
                        <a href="{{ route('admin.catalog.index') }}">Moderação</a>
                        <a href="{{ route('admin.manage') }}">Gerenciar catálogo</a>
                    @endcan
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                    <a href="{{ route('register') }}">Criar conta</a>
                @endauth
            </nav>
        </details>
        <a href="{{ route('home') }}" class="sm-brand" aria-label="Salada Mix — página inicial">
            <img src="{{ asset('assets/salada/salada-mix-logo.svg') }}" alt="Salada Mix" width="178" height="36">
        </a>
        <form class="sm-search" method="get" action="{{ route('storefront.search') }}" role="search">
            <label class="sr-only" for="sm-global-search">Buscar produtos ou lojas</label>
            <input id="sm-global-search" name="q" type="search" value="{{ request()->routeIs('storefront.search') ? request()->query('q', '') : '' }}" maxlength="100" placeholder="Busque produtos ou lojas">
            <button type="submit" aria-label="Buscar produtos"><x-salada.icon name="search" size="21" /><span>Buscar</span></button>
        </form>
        <nav class="sm-actions" aria-label="Acesso rápido">
            <span class="sm-action is-unavailable" title="Cálculo de frete disponível em uma próxima entrega" aria-label="CEP em preparação">
                <x-salada.icon name="pin" size="22" /><span class="sm-action-copy"><small>Enviar para</small><strong>Informe seu CEP</strong></span>
            </span>
            @auth
                <a class="sm-action" href="{{ route('buyer.account') }}">
                    <x-salada.icon name="user" size="22" /><span class="sm-action-copy"><small>Olá, {{ \Illuminate\Support\Str::limit(auth()->user()->name, 12) }}</small><strong>Minha conta</strong></span>
                </a>
            @else
                <a class="sm-action" href="{{ route('login') }}">
                    <x-salada.icon name="user" size="22" /><span class="sm-action-copy"><small>Bem-vindo(a)</small><strong>Entre ou cadastre-se</strong></span>
                </a>
            @endauth
            @auth
                @php($shoppingCount = (int) \Illuminate\Support\Facades\DB::table('cart_items')->where('user_id', auth()->id())->sum('quantity'))
                <a class="sm-action sm-cart-action" href="{{ route('buyer.cart.page') }}" aria-label="Minha sacola com {{ $shoppingCount }} itens">
                    <x-salada.icon name="cart" size="25" />
                    @if($shoppingCount > 0)<span class="sm-cart-count" aria-hidden="true">{{ min($shoppingCount, 99) }}</span>@endif
                </a>
                <a class="sm-action" href="{{ route('buyer.wishlist.page') }}" aria-label="Meus favoritos" title="Favoritos">♡</a>
            @else
                <a class="sm-action" href="{{ route('login') }}" aria-label="Entre para acessar sua sacola"><x-salada.icon name="cart" size="25" /></a>
            @endauth
        </nav>
    </div>
</header>
<div class="sm-nav">
    <nav class="sm-container sm-nav-inner" aria-label="Departamentos e área institucional">
        <a href="{{ route('storefront.search') }}">☰ &nbsp;Todos os produtos</a>
        @foreach ($navCategories->take(4) as $navCategory)
            <a href="{{ route('storefront.category', $navCategory) }}">{{ $navCategory->name }}</a>
        @endforeach
        <span class="sm-nav-spacer"></span>
        @can('review-sellers')
            <a href="{{ route('admin.sellers.index') }}">Administração</a>
            <a href="{{ route('admin.catalog.index') }}">Moderação</a>
            <a href="{{ route('admin.manage') }}">Gerenciar catálogo</a>
        @endcan
        <a href="{{ route('seller.apply') }}">Quero vender</a>
    </nav>
</div>
