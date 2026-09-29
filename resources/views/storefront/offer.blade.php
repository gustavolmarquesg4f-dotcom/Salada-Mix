@extends('layouts.app')
@section('title', $offer->product->name.' — Salada Mix')
@section('content')
@php($demoImage = \App\Support\DemoMedia::product($offer))
@if($demoImage)<div class="sm-demo-label"><strong>PRODUTO DE DEMONSTRAÇÃO</strong><span>Imagem ilustrativa e dados sintéticos. Não disponível para compra.</span></div>@endif
<nav class="sm-breadcrumb" aria-label="Caminho de navegação">
    <a href="{{ route('home') }}">Início</a><span aria-hidden="true">/</span>
    <a href="{{ route('storefront.category', $offer->product->category) }}">{{ $offer->product->category->name }}</a><span aria-hidden="true">/</span>
    <span aria-current="page">{{ $offer->product->name }}</span>
</nav>
<article class="sm-detail sm-detail-premium">
    <div class="sm-detail-gallery">
        @if($demoImage)
            <img class="sm-detail-image" src="{{ $demoImage }}" alt="Imagem ilustrativa do produto {{ $offer->product->name }}" width="720" height="720" referrerpolicy="no-referrer">
            <p>Imagem meramente ilustrativa, vinculada apenas ao catálogo sintético.</p>
        @else
            <div class="sm-detail-visual" role="img" aria-label="Imagem do produto ainda não cadastrada">{{ $offer->product->category->name }}</div>
            <p>Foto ainda não cadastrada pelo vendedor.</p>
        @endif
    </div>
    <div class="sm-detail-info">
        <span class="sm-detail-category">{{ $offer->product->category->name }}</span>
        <p class="sm-eyebrow">Vendido por {{ $offer->seller->trade_name }}</p>
        <h1>{{ $offer->product->name }}</h1>
        <p class="sm-detail-sku">Referência do vendedor: {{ $offer->sku }}</p>
        <p class="sm-detail-price">R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</p>
        <p class="sm-detail-stock">Disponível para consulta · {{ max(0, ($offer->stock?->quantity_on_hand ?? 0) - ($offer->stock?->quantity_reserved ?? 0)) }} unidades cadastradas</p>
        <div class="sm-notice info"><strong>O checkout está desativado.</strong> Estamos preparando pagamentos e logística para compras entre diferentes vendedores.</div>
        <div class="sm-detail-buy-actions"><x-salada.commerce-actions :offer="$offer" compact /></div>
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.category', $offer->product->category) }}">Mais neste departamento →</a>
    </div>
    <section class="sm-detail-description" aria-labelledby="sm-detail-description-title">
        <h2 id="sm-detail-description-title">Descrição do produto</h2>
        <p>{{ $offer->product->description ?: 'O vendedor ainda não cadastrou uma descrição detalhada.' }}</p>
    </section>
</article>
@endsection
