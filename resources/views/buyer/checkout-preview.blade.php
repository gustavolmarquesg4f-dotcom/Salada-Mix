@extends('layouts.app')
@section('title', 'Resumo da sacola — Salada Mix')
@section('content')
<div class="mx-auto max-w-4xl">
    <p class="text-sm font-bold uppercase tracking-wide text-violet-700">Sua sacola</p>
    <h1 class="mt-2 text-3xl font-black">Resumo por vendedor</h1>
    <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">{{ $preview['reason'] }} Este resumo não é uma cobrança, reserva ou pedido.</p>
    @forelse($preview['groups'] as $group)
        <section class="mt-5 rounded-2xl border bg-white p-6">
            <h2 class="text-lg font-black">{{ $group['seller']['name'] }}</h2>
            @foreach($group['items'] as $line)
                <div class="mt-3 flex justify-between gap-3 text-sm">
                    <span>{{ $line['quantity'] }} × {{ $line['offer']['name'] }}</span>
                    <strong>R$ {{ number_format($line['line_total_cents'] / 100, 2, ',', '.') }}</strong>
                </div>
            @endforeach
            <p class="mt-4 text-right font-bold">Produtos: R$ {{ number_format($group['items_subtotal_cents'] / 100, 2, ',', '.') }}</p>
            <p class="mt-1 text-right text-sm text-amber-800">Frete: ainda sem cotação real.</p>
        </section>
    @empty
        <p class="mt-5">Sua sacola não possui ofertas disponíveis.</p>
    @endforelse
    @if($preview['unavailable_items_count'])
        <p class="mt-4 text-amber-800">{{ $preview['unavailable_items_count'] }} item(ns) indisponível(is) precisam ser revisados.</p>
    @endif
    <p class="mt-5 text-xl font-black">Subtotal de produtos: R$ {{ number_format($preview['items_subtotal_cents'] / 100, 2, ',', '.') }}</p>
    <button disabled class="mt-5 cursor-not-allowed rounded-xl bg-slate-300 px-6 py-3 font-bold text-slate-700">Pagamento indisponível nesta etapa</button>
    <a class="mt-5 ml-3 inline-block font-semibold text-violet-700" href="{{ route('buyer.addresses.index') }}">Gerenciar endereços</a>
</div>
@endsection
