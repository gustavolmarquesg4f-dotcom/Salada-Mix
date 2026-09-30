@extends('layouts.app')
@section('title', 'Visão de '.ucfirst($role).' — Demonstração Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('storefront.demo') }}">Demonstração</a><span>/</span><span>{{ ucfirst($role) }}</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">VISUALIZAÇÃO SINTÉTICA · SEM PRIVILÉGIOS ADMINISTRATIVOS</span>
    <h1>Painel demonstrativo: {{ ucfirst($role) }}</h1>
    <p>Este quadro consulta lojas e ofertas reais de demonstração no banco, mas não concede acesso a uma conta de vendedor ou administrador. Para alterar produtos ou aprovar anúncios, entre com uma conta autorizada e MFA quando exigido.</p>
    <div class="sm-preview-grid mt-6">
        <div class="sm-preview-stat"><strong>{{ $shops->count() }}</strong><span>lojas de demonstração</span></div>
        <div class="sm-preview-stat"><strong>{{ $offers->count() }}</strong><span>ofertas no banco</span></div>
        <div class="sm-preview-stat"><strong>{{ $ordersCount }}</strong><span>pedidos fictícios isolados</span></div>
    </div>
    <div class="sm-preview-actions mt-6">
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Ver ofertas reais do banco</a>
        @if($role === 'comprador')
            <a class="sm-btn sm-btn-primary" href="{{ route('storefront.demo') }}">Testar sacola e pedido fictício</a>
        @elseif($role === 'vendedor')
            <a class="sm-btn sm-btn-primary" href="{{ route('seller.apply') }}">Inscrição real de vendedor</a>
        @else
            <a class="sm-btn sm-btn-primary" href="{{ route('admin.manage') }}">Acessar administração protegida</a>
            <a class="sm-btn sm-btn-secondary" href="{{ route('admin.catalog.index') }}">Fila real de moderação</a>
        @endif
    </div>
</section>
<section class="sm-account-panel mt-6"><h2>Dados persistidos no catálogo</h2>
@foreach($shops as $shop)<div class="sm-checkout-seller"><h3>{{ $shop->trade_name }}</h3><small>{{ $shop->status }}</small></div>@endforeach
@foreach($offers as $offer)
<div class="sm-shopping-line"><div><strong>{{ $offer->name }}</strong><p>{{ $offer->trade_name }} · SKU {{ $offer->sku }}</p></div>
<strong>R$ {{ number_format($offer->price_cents/100,2,',','.') }}</strong>
<a href="{{ route('storefront.offer', $offer->id) }}">Abrir produto →</a></div>
@endforeach
</section>
@endsection
