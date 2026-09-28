@extends('layouts.app')
@section('title', $category->name.' — Salada Mix')
@section('content')
<nav class="text-sm text-slate-500"><a href="{{ route('home') }}">Início</a> / {{ $category->name }}</nav>
<h1 class="mt-5 text-3xl font-black">{{ $category->name }}</h1>
<div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    @forelse ($offers as $offer)
        @include('storefront._offer-card', ['offer' => $offer])
    @empty
        <p class="col-span-full rounded-2xl bg-white p-8 text-slate-600">Nenhuma oferta habilitada neste departamento ainda.</p>
    @endforelse
</div>
<div class="mt-8">{{ $offers->links() }}</div>
@endsection

