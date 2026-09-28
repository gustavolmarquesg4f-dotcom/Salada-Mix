@extends('layouts.app')
@section('title', 'Salada Mix — Tudo num só lugar')
@section('content')
<section class="rounded-3xl bg-teal-800 px-6 py-14 text-white md:px-12">
    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider">Marketplace aberto · em preparação</span>
    <h1 class="mt-6 max-w-3xl text-4xl font-black leading-tight md:text-6xl">Tudo num só lugar, com espaço para a sua loja.</h1>
    <p class="mt-5 max-w-2xl text-lg text-teal-50">Departamentos e ofertas de diferentes empresas, com a segurança de uma plataforma que aprova seus vendedores.</p>
    <div class="mt-8 flex flex-wrap gap-3">
        <a class="rounded-xl bg-orange-400 px-6 py-3 font-bold text-slate-950 hover:bg-orange-300" href="{{ route('seller.apply') }}">Cadastrar empresa</a>
        <a class="rounded-xl border border-white/60 px-6 py-3 font-bold text-white hover:bg-white/10" href="{{ route('register') }}">Criar conta de cliente</a>
    </div>
</section>
<section id="departamentos" class="mt-12">
    <h2 class="text-3xl font-black">Explore os departamentos</h2>
    <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-5">
        @forelse ($categories as $category)
            <a href="{{ route('storefront.category', $category) }}" class="rounded-2xl border bg-white p-5 font-semibold hover:border-teal-600 hover:text-teal-700">{{ $category->name }}</a>
        @empty
            <p class="col-span-full rounded-xl bg-white p-5 text-slate-600">O catálogo será disponibilizado em breve.</p>
        @endforelse
    </div>
</section>
<section class="mt-12">
    <h2 class="text-3xl font-black">Ofertas disponíveis</h2>
    <p class="mt-2 text-slate-600">Produtos exibidos somente após revisão e habilitação da empresa.</p>
    <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @forelse ($offers as $offer)
            @include('storefront._offer-card', ['offer' => $offer])
        @empty
            <div class="col-span-full rounded-2xl border border-dashed bg-white p-10 text-center text-slate-600">
                As empresas estão sendo credenciadas. As ofertas aparecerão após aprovação e habilitação comercial.
            </div>
        @endforelse
    </div>
</section>
@endsection

