@extends('layouts.app')
@section('title', 'Moderação de produtos — Salada Mix')
@section('content')
<h1 class="text-3xl font-black">Moderação do catálogo</h1>
<p class="mt-3 text-slate-600">Revisão do produto e da oferta. A empresa também precisa estar comercialmente ativa para aparecer na vitrine.</p>
<div class="mt-7 space-y-4">
@forelse ($offers as $offer)
    <article class="rounded-2xl border bg-white p-6">
        <h2 class="text-xl font-bold">{{ $offer->product->name }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $offer->seller->trade_name }} · CNPJ {{ $offer->seller->cnpj }} · Status da empresa: {{ $offer->seller->status }}</p>
        <p class="mt-2 text-sm text-slate-600">SKU {{ $offer->sku }} · Categoria {{ $offer->product->category->name }} · R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }} · Quantidade {{ $offer->stock?->quantity_on_hand ?? 0 }}</p>
        <p class="mt-3 whitespace-pre-line text-slate-700">{{ $offer->product->description }}</p>
        <div class="mt-5 flex flex-wrap gap-3">
            <form action="{{ route('admin.catalog.approve', $offer) }}" method="post">@csrf<button class="rounded-xl bg-teal-700 px-5 py-2 font-bold text-white">Aprovar oferta</button></form>
            <form action="{{ route('admin.catalog.reject', $offer) }}" method="post" class="flex flex-wrap gap-2">
                @csrf
                <label class="sr-only" for="reason-{{ $offer->id }}">Motivo da rejeição</label>
                <input id="reason-{{ $offer->id }}" name="reason" required minlength="10" maxlength="2000" placeholder="Motivo da rejeição" class="rounded-lg border p-2">
                <button class="rounded-xl border border-rose-600 px-5 py-2 font-bold text-rose-700">Rejeitar</button>
            </form>
        </div>
    </article>
@empty
    <p class="rounded-2xl border border-dashed bg-white p-8 text-slate-600">Nenhuma oferta pendente.</p>
@endforelse
</div>
<div class="mt-7">{{ $offers->links() }}</div>
@endsection

