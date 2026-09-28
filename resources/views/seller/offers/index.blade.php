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
    </div>
@empty
    <p class="rounded-2xl border border-dashed bg-white p-8 text-slate-600">Nenhum produto cadastrado.</p>
@endforelse
</div>
<div class="mt-6">{{ $offers->links() }}</div>
@endsection

