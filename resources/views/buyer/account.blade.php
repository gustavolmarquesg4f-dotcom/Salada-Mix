@extends('layouts.app')
@section('title', 'Minha conta — Salada Mix')
@section('content')
@php
    $user = auth()->user();
    $identities = $user->socialIdentities()->orderBy('provider')->get();
    $providers = collect(config('sso.providers', []))->filter(fn ($provider) => (bool) ($provider['enabled'] ?? false));
@endphp
<h1 class="text-3xl font-black">Minha conta</h1>
<p class="mt-3 text-slate-600">Olá, {{ $user->name }}. Seu e-mail verificado: {{ $user->email }}.</p>
<p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">O checkout financeiro ainda não está disponível. Você já pode cadastrar endereços e conferir os itens da sacola por vendedor.</p>

<section class="mt-6 rounded-2xl border bg-white p-6">
    <h2 class="text-xl font-black">Login e segurança</h2>
    <p class="mt-2 text-sm text-slate-600">Senha: {{ $user->password_login_enabled ? 'habilitada' : 'ainda não definida' }}.</p>
    <div class="mt-4 space-y-3">
        @forelse ($identities as $identity)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4">
                <div><strong>{{ config("sso.providers.{$identity->provider}.label", ucfirst($identity->provider)) }}</strong><p class="text-xs text-slate-500">{{ $identity->provider_email }}</p></div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Conectado</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">Nenhum provedor externo conectado.</p>
        @endforelse
    </div>
    @if ($providers->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ($providers as $id => $provider)
                @if (! $identities->contains('provider', $id))
                    <a href="{{ route('sso.redirect', $id) }}" class="rounded-xl border px-4 py-2 text-sm font-bold text-teal-700">Conectar {{ $provider['label'] ?? ucfirst($id) }}</a>
                @endif
            @endforeach
        </div>
    @endif
    <p class="mt-4 text-xs text-slate-500">Desconectar provedores e definir senha para contas SSO são operações protegidas pelo BFF, com reautenticação recente.</p>
</section>

<div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('buyer.addresses.index') }}" class="rounded-xl bg-violet-700 px-5 py-3 font-bold text-white">Meus endereços</a><a href="{{ route('buyer.checkout.preview') }}" class="rounded-xl border px-5 py-3 font-bold">Resumo da sacola</a></div>
@endsection

