@extends('layouts.app')
@section('title', 'Minha empresa — Salada Mix')
@section('content')
<p class="text-sm font-bold uppercase tracking-wider text-teal-700">Portal da empresa</p>
<h1 class="mt-2 text-3xl font-black">{{ $seller->trade_name }}</h1>
<p class="mt-3 text-slate-600">Razão social: {{ $seller->legal_name }}</p>
<div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <h2 class="font-bold">Situação do cadastro: {{ $seller->status }}</h2>
    <p class="mt-2 text-sm">A habilitação de vendas exige análise, configuração financeira, logística e catálogo. O checkout permanece desligado.</p>
</div>
<a href="{{ route('seller.offers.index', $seller) }}" class="mt-5 inline-flex rounded-xl bg-teal-700 px-5 py-3 font-bold text-white">Gerenciar meus produtos</a>
<a href="{{ route('seller.origins.index', $seller) }}" class="ml-3 mt-5 inline-flex rounded-xl border px-5 py-3 font-bold">Origens de envio</a>
<a href="{{ route('seller.team.index', $seller) }}" class="ml-3 mt-5 inline-flex rounded-xl border px-5 py-3 font-bold">Equipe e permissões</a>
@endsection
