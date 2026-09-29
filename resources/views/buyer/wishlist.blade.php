@extends('layouts.app')
@section('title', 'Meus favoritos — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('buyer.account') }}">Minha conta</a><span>/</span><span aria-current="page">Favoritos</span></nav>
<div class="sm-shopping-title"><div><span class="sm-eyebrow">Seus próximos achados</span><h1>Meus favoritos <span>({{ count($items) }})</span></h1><p>Salve produtos e volte a eles quando quiser. A disponibilidade pode mudar.</p></div><a class="sm-btn sm-btn-secondary" href="{{ route('buyer.cart.page') }}">Ver minha sacola →</a></div>
<div class="sm-shopping-alert" role="status">Os favoritos são privados da sua conta. Comprar e calcular frete continuam indisponíveis nesta etapa.</div>
@if(count($items) === 0)
<section class="sm-shopping-empty"><span class="sm-empty-symbol" aria-hidden="true">♡</span><h2>Seus favoritos vão aparecer aqui.</h2><p>Encontre produtos que combinam com você e toque no coração para salvá-los.</p><a class="sm-btn sm-btn-primary" href="{{ route('storefront.search') }}">Descobrir produtos →</a></section>
@else
<div class="sm-favorites-grid">
    @foreach($items as $item)
    @php($photo = \App\Support\DemoMedia::productSlug($item['slug']))
    <article class="sm-favorite-card">
        <a href="{{ $item['url'] }}" class="sm-favorite-thumb">
            @if($photo)<img src="{{ $photo }}" alt="Imagem ilustrativa: {{ $item['name'] }}" loading="lazy" width="360" height="280" referrerpolicy="no-referrer"><span>DEMO</span>@else<span class="sm-favorite-noimage">{{ $item['category']['name'] }}</span>@endif
        </a>
        <div class="sm-favorite-body"><small>{{ $item['seller']['name'] }}</small><a class="sm-favorite-name" href="{{ $item['url'] }}">{{ $item['name'] }}</a><strong>R$ {{ number_format($item['price_cents'] / 100, 2, ',', '.') }}</strong><p>Preço sujeito a atualização.</p>
        <div class="sm-favorite-actions"><button type="button" class="sm-btn sm-btn-primary" data-sm-commerce="cart-add" data-url="{{ route('bff.cart.put', ['offer' => $item['id']]) }}">Adicionar à sacola</button><button type="button" class="sm-shopping-remove" data-sm-commerce="wishlist-remove" data-url="{{ route('bff.wishlist.destroy', ['offer' => $item['id']]) }}">Remover ♡</button></div></div>
    </article>
    @endforeach
</div>
@endif
@endsection
