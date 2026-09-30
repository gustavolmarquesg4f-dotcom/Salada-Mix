@extends('layouts.app')
@section('title', 'Produtos da empresa — Salada Mix')
@section('content')
<nav class="text-sm text-slate-500"><a href="{{ route('seller.dashboard', $seller) }}">Painel da empresa</a> / Produtos</nav>
<div class="mt-4 flex flex-wrap items-center justify-between gap-4">
    <div><h1 class="text-3xl font-black">Produtos de {{ $seller->trade_name }}</h1><p class="mt-2 text-slate-600">Ofertas da sua empresa; aprovação não libera automaticamente a venda.</p></div>
    @if ($seller->memberships()->where('user_id', auth()->id())->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists())
        <a href="{{ route('seller.offers.create', $seller) }}" class="rounded-xl bg-teal-700 px-5 py-3 font-bold text-white">Cadastrar produto</a>
    @endif
</div>
<div class="mt-7 space-y-3">
@forelse ($offers as $offer)
    <div class="rounded-2xl border bg-white p-5">
        <h2 class="text-lg font-bold">{{ $offer->product->name }}</h2>
        <p class="mt-1 text-sm text-slate-600">SKU {{ $offer->sku }} · R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }} · Estoque {{ $offer->stock?->quantity_on_hand ?? 0 }} · Análise: {{ $offer->review_status }}</p>
        @foreach($offer->product->media as $image)<img src="{{ route('media.show', $image) }}" alt="{{ $image->alt }}" width="100" height="100" class="mt-3 inline-block rounded-lg object-cover">@endforeach
        @if($seller->memberships()->where('user_id', auth()->id())->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists())
        <form method="post" action="{{ route('seller.offers.media.store', [$seller, $offer]) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
            @csrf
            <label>Foto do produto (JPEG, PNG ou WebP; 300–4000 px; até 4 MB)
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="block mt-1">
            </label>
            <label>Texto alternativo <input name="alt" required maxlength="160" minlength="3" class="block rounded border p-2"></label>
            <button class="rounded-lg bg-teal-700 px-4 py-2 font-bold text-white">Salvar foto</button>
        </form>
        @endif
    </div>
@empty
    <p class="rounded-2xl border border-dashed bg-white p-8 text-slate-600">Nenhum produto cadastrado.</p>
@endforelse
</div>
<div class="mt-6">{{ $offers->links() }}</div>
@endsection

