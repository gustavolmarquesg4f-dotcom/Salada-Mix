@extends('layouts.app')
@section('title', $offer->product->name.' — Salada Mix')
@section('content')
<nav class="text-sm text-slate-500"><a href="{{ route('home') }}">Início</a> / <a href="{{ route('storefront.category', $offer->product->category) }}">{{ $offer->product->category->name }}</a></nav>
<div class="mt-8 grid gap-8 md:grid-cols-2">
    <div class="flex min-h-80 items-center justify-center rounded-3xl bg-gradient-to-br from-teal-50 to-slate-100 text-3xl font-black text-teal-700">{{ $offer->product->category->name }}</div>
    <div>
        <p class="text-sm font-bold uppercase text-teal-700">Vendido por {{ $offer->seller->trade_name }}</p>
        <h1 class="mt-3 text-4xl font-black">{{ $offer->product->name }}</h1>
        <p class="mt-5 text-4xl font-black">R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</p>
        <p class="mt-5 whitespace-pre-line text-slate-600">{{ $offer->product->description ?: 'Descrição em atualização.' }}</p>
        <div class="mt-7 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-950">O checkout está desativado durante a preparação comercial do Salada Mix.</div>
    </div>
</div>
@endsection

