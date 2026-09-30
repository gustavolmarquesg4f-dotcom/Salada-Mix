@extends('layouts.app')
@section('title', 'Produtos da empresa — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('seller.dashboard', $seller) }}">Painel da empresa</a><span>/</span><span>Produtos</span></nav>
    <div class="sm-portal-heading">
        <div><span class="sm-portal-overline">Catálogo da loja</span><h1>Produtos de {{ $seller->trade_name }}</h1><p>Cadastre produtos e acompanhe estoque, preço, imagens e situação de moderação.</p></div>
        @if ($seller->memberships()->where('user_id', auth()->id())->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists())
            <a href="{{ route('seller.offers.create', $seller) }}" class="sm-btn sm-btn-primary">+ Cadastrar produto</a>
        @endif
    </div>
    <div class="sm-portal-list">
    @forelse ($offers as $offer)
        <article class="sm-portal-card sm-seller-product-card">
            <div class="sm-seller-product-top">
                <div><span class="sm-portal-badge">{{ $offer->review_status }}</span><h2>{{ $offer->product->name }}</h2><p>SKU {{ $offer->sku }} · {{ $offer->product->category->name }}</p></div>
                <div class="sm-seller-product-metrics"><strong>R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</strong><small>Estoque: {{ $offer->stock?->quantity_on_hand ?? 0 }} · reservado: {{ $offer->stock?->quantity_reserved ?? 0 }}</small></div>
            </div>
            @if($offer->product->media->isNotEmpty())
                <div class="sm-seller-media-row">@foreach($offer->product->media as $image)<img src="{{ route('media.show', $image) }}" alt="{{ $image->alt }}" width="100" height="100">@endforeach</div>
            @endif
            @if($seller->memberships()->where('user_id', auth()->id())->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists())
            <details class="sm-portal-details">
                <summary>Adicionar foto ao produto</summary>
                <form method="post" action="{{ route('seller.offers.media.store', [$seller, $offer]) }}" enctype="multipart/form-data" class="sm-portal-form sm-portal-form-inline">
                    @csrf
                    <label>Foto JPEG, PNG ou WebP
                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                    <label>Texto alternativo
                        <input name="alt" required maxlength="160" minlength="3" placeholder="Descreva a imagem">
                    </label>
                    <button class="sm-btn sm-btn-secondary" type="submit">Salvar foto</button>
                </form>
            </details>
            @endif
        </article>
    @empty
        <div class="sm-empty"><strong>Nenhum produto cadastrado.</strong><p>Comece criando o primeiro item da sua loja.</p></div>
    @endforelse
    </div>
    <div class="mt-6">{{ $offers->links() }}</div>
</div>
@endsection
