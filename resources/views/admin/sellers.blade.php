@extends('layouts.app')
@section('title', 'Empresas — Administração Salada Mix')
@section('content')
<h1 class="text-3xl font-black">Análise de empresas</h1>
<p class="mt-3 text-slate-600">Aprovação comercial não habilita automaticamente recebimentos ou anúncios.</p>
<div class="mt-6 space-y-4">
    @forelse ($sellers as $seller)
        <article class="rounded-2xl border bg-white p-5">
            <h2 class="text-lg font-bold">{{ $seller->trade_name }} <span class="text-sm font-normal text-slate-500">({{ $seller->status }})</span></h2>
            <p class="text-sm text-slate-600">CNPJ {{ $seller->cnpj }} · Responsável {{ $seller->owner->name }}</p>
            @if (in_array($seller->status, ['submitted', 'under_review'], true))
                <div class="mt-4 flex flex-wrap gap-3">
                    <form action="{{ route('admin.sellers.approve', $seller) }}" method="post">@csrf<button class="rounded-lg bg-teal-700 px-4 py-2 font-bold text-white">Aprovar cadastro</button></form>
                    <form action="{{ route('admin.sellers.reject', $seller) }}" method="post" class="flex flex-wrap gap-2">
                        @csrf
                        <label class="sr-only" for="reason-{{ $seller->id }}">Motivo</label>
                        <input id="reason-{{ $seller->id }}" name="reason" required minlength="10" maxlength="2000" placeholder="Motivo da rejeição" class="rounded-lg border p-2">
                        <button class="rounded-lg border border-rose-600 px-4 py-2 font-bold text-rose-700">Rejeitar</button>
                    </form>
                </div>
            @endif
        </article>
    @empty <p>Nenhuma solicitação recebida.</p>
    @endforelse
</div>
<div class="mt-6">{{ $sellers->links() }}</div>
@endsection

