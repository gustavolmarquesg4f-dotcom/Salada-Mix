@extends('layouts.app')
@section('title', ($category?->name ?? 'Buscar produtos').' — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação">
    <a href="{{ route('home') }}">Início</a><span aria-hidden="true">/</span>
    <span aria-current="page">{{ $category?->name ?? 'Buscar' }}</span>
</nav>
<div class="sm-browse-heading">
    <div>
        <span class="sm-eyebrow">Explore o seu mix</span>
        <h1>{{ $category?->name ?? 'Encontre o que procura' }}</h1>
        <p>{{ $offers->total() }} {{ $offers->total() === 1 ? 'oferta disponível' : 'ofertas disponíveis' }}{{ filled($filters['q'] ?? null) ? ' para "'.$filters['q'].'"' : '' }}.</p>
    </div>
</div>
<div class="sm-browse-layout">
    <aside class="sm-filter-panel" aria-label="Filtros de produtos">
        <h2>Refinar busca</h2>
        <form method="get" action="{{ $category ? route('storefront.category', $category) : route('storefront.search') }}">
            <label for="sm-filter-q">Buscar por produto ou loja</label>
            <input id="sm-filter-q" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Nome ou palavra-chave">
            @unless ($category)
                <label for="sm-filter-category">Departamento</label>
                <select id="sm-filter-category" name="category">
                    <option value="">Todos os departamentos</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->slug }}" @selected(($filters['category'] ?? '') === $item->slug)>{{ $item->name }}</option>
                    @endforeach
                </select>
            @endunless
            <label for="sm-filter-seller">Vendido por</label>
            <select id="sm-filter-seller" name="seller">
                <option value="">Todas as lojas</option>
                @foreach ($sellers as $seller)
                    <option value="{{ $seller->id }}" @selected(($filters['seller'] ?? '') === $seller->id)>{{ $seller->trade_name }}</option>
                @endforeach
            </select>
            <span class="sm-filter-label">Preço (R$)</span>
            <div class="sm-price-fields">
                <div><label for="sm-min-price">De</label><input id="sm-min-price" name="min_price" type="text" inputmode="decimal" pattern="[0-9]{1,7}([.,][0-9]{1,2})?" maxlength="10" value="{{ $filters['min_price'] ?? '' }}" placeholder="0,00"></div>
                <div><label for="sm-max-price">Até</label><input id="sm-max-price" name="max_price" type="text" inputmode="decimal" pattern="[0-9]{1,7}([.,][0-9]{1,2})?" maxlength="10" value="{{ $filters['max_price'] ?? '' }}" placeholder="999,00"></div>
            </div>
            <label for="sm-filter-sort">Ordenar por</label>
            <select id="sm-filter-sort" name="sort">
                <option value="recent" @selected(($filters['sort'] ?? 'recent') === 'recent')>Mais recentes</option>
                <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Menor preço</option>
                <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Maior preço</option>
            </select>
            <button class="sm-btn sm-btn-primary sm-filter-submit" type="submit">Aplicar filtros</button>
            <a class="sm-filter-clear" href="{{ $category ? route('storefront.category', $category) : route('storefront.search') }}">Limpar filtros</a>
        </form>
    </aside>
    <section class="sm-browse-results" aria-label="Resultados da busca">
        <div class="sm-result-bar">
            <span>Exibindo {{ $offers->firstItem() ?? 0 }}–{{ $offers->lastItem() ?? 0 }} de {{ $offers->total() }}</span>
            <span>Produtos de empresas habilitadas</span>
        </div>
        <div class="sm-offer-grid">
            @forelse ($offers as $offer)
                @include('storefront._offer-card', ['offer' => $offer])
            @empty
                <div class="sm-empty">
                    <strong>Nenhuma oferta encontrada com esses filtros.</strong>
                    <p>Tente outra palavra-chave, remova um filtro ou explore os departamentos disponíveis.</p>
                    <a class="sm-btn sm-btn-secondary" href="{{ $category ? route('storefront.category', $category) : route('storefront.search') }}">Limpar filtros</a>
                </div>
            @endforelse
        </div>
        @if ($offers->hasPages())
            <nav class="sm-pagination" aria-label="Páginas de resultados">{{ $offers->links() }}</nav>
        @endif
    </section>
</div>
@endsection
