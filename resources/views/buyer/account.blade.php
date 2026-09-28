@extends('layouts.app')
@section('title', 'Minha conta — Salada Mix')
@section('content')
<h1 class="text-3xl font-black">Minha conta</h1>
<p class="mt-3 text-slate-600">Olá, {{ auth()->user()->name }}. Seu e-mail verificado: {{ auth()->user()->email }}.</p>
<p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">O checkout financeiro ainda não está disponível. Você já pode cadastrar endereços e conferir os itens da sacola por vendedor.</p>
<div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('buyer.addresses.index') }}" class="rounded-xl bg-violet-700 px-5 py-3 font-bold text-white">Meus endereços</a><a href="{{ route('buyer.checkout.preview') }}" class="rounded-xl border px-5 py-3 font-bold">Resumo da sacola</a></div>
@endsection

