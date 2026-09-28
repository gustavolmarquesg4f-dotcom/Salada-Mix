@extends('layouts.app')
@section('title', 'Minha conta — Salada Mix')
@section('content')
<h1 class="text-3xl font-black">Minha conta</h1>
<p class="mt-3 text-slate-600">Olá, {{ auth()->user()->name }}. Seu e-mail verificado: {{ auth()->user()->email }}.</p>
<p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">Histórico de pedidos e endereços serão disponibilizados na fase comercial. Nenhuma compra está habilitada neste ambiente.</p>
@endsection

