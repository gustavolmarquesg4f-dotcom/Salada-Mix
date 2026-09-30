@extends('layouts.app')
@section('title', 'Moderação de produtos — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('admin.manage') }}">Administração</a><span>/</span><span>Moderação</span></nav>
    <div class="sm-portal-heading"><div><span class="sm-portal-overline">Administração · MFA</span><h1>Moderação do catálogo</h1><p>Revise produto, preço, estoque, imagens e vendedor antes de liberar uma oferta para a vitrine.</p></div><a class="sm-btn sm-btn-secondary" href="{{ route('admin.manage') }}">Gerenciar catálogo →</a></div>
    <div class="sm-portal-list">
    @forelse ($offers as $offer)
        <article class="sm-portal-card">
            <div class="sm-seller-product-top"><div><span class="sm-portal-badge">{{ $offer->review_status }}</span><h2>{{ $offer->product->name }}</h2><p>{{ $offer->seller->trade_name }} · CNPJ {{ $offer->seller->cnpj }} · empresa {{ $offer->seller->status }}</p></div><div class="sm-seller-product-metrics"><strong>R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</strong><small>SKU {{ $offer->sku }} · {{ $offer->product->category->name }} · {{ $offer->stock?->quantity_on_hand ?? 0 }} un.</small></div></div>
            @if($offer->product->description)<p class="sm-admin-product-description">{{ $offer->product->description }}</p>@endif
            @if($offer->product->media->isNotEmpty())<div class="sm-seller-media-row">@foreach($offer->product->media as $image)<img src="{{ route('media.show', $image) }}" alt="{{ $image->alt }}" width="120" height="120">@endforeach</div>@endif
            <div class="sm-admin-review-actions">
                <form action="{{ route('admin.catalog.approve', $offer) }}" method="post">@csrf<button class="sm-btn sm-btn-primary">Aprovar oferta</button></form>
                <form action="{{ route('admin.catalog.reject', $offer) }}" method="post" class="sm-admin-reject-form">@csrf<label class="sr-only" for="reason-{{ $offer->id }}">Motivo da rejeição</label><input id="reason-{{ $offer->id }}" name="reason" required minlength="10" maxlength="2000" placeholder="Motivo da rejeição"><button class="sm-btn sm-btn-secondary">Rejeitar</button></form>
            </div>
        </article>
    @empty <div class="sm-empty"><strong>Nenhuma oferta pendente.</strong><p>Quando vendedores enviarem novos produtos, eles aparecerão aqui.</p></div>@endforelse
    </div>
    <div class="mt-7">{{ $offers->links() }}</div>
</div>
@endsection
