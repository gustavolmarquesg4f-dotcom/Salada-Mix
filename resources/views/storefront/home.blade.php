@extends('layouts.app')
@section('title', 'Salada Mix — Tudo num só lugar')
@section('content')
<section class="rounded-3xl bg-teal-800 px-6 py-14 text-white md:px-12">
    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider">Marketplace aberto · em preparação</span>
    <h1 class="mt-6 max-w-3xl text-4xl font-black leading-tight md:text-6xl">Tudo num só lugar, com espaço para a sua loja.</h1>
    <p class="mt-5 max-w-2xl text-lg text-teal-50">Conheça a nova fundação do Salada Mix. O cadastro de vendedores está disponível para análise; as vendas ainda não foram liberadas.</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a class="rounded-xl bg-orange-400 px-6 py-3 font-bold text-slate-950 hover:bg-orange-300" href="{{ route('seller.apply') }}">Cadastrar empresa</a>
        <a class="rounded-xl border border-white/60 px-6 py-3 font-bold text-white hover:bg-white/10" href="{{ route('register') }}">Criar conta de cliente</a>
    </div>
</section>
<section class="mt-10 grid gap-4 md:grid-cols-3" aria-label="Áreas da plataforma">
    <article class="rounded-2xl border bg-white p-6"><h2 class="text-xl font-bold">Compradores</h2><p class="mt-2 text-slate-600">Conta, endereços e futuros pedidos em um único lugar.</p></article>
    <article class="rounded-2xl border bg-white p-6"><h2 class="text-xl font-bold">Empresas</h2><p class="mt-2 text-slate-600">Cadastro público com análise e aprovação da plataforma.</p></article>
    <article class="rounded-2xl border bg-white p-6"><h2 class="text-xl font-bold">Governança</h2><p class="mt-2 text-slate-600">Papéis separados, rastreabilidade e segurança por empresa.</p></article>
</section>
@endsection

