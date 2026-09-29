@extends('layouts.app')
@section('title', 'Equipe — Salada Mix')
@section('content')
<p class="text-sm font-bold uppercase tracking-wider text-violet-700">Portal do vendedor</p>
<h1 class="mt-2 text-3xl font-black">Equipe de {{ $seller->trade_name }}</h1>
<p class="mt-2 text-slate-600">Apenas o responsável da empresa pode convidar ou revogar acessos. Convites vencem em 48 horas.</p>

<section class="mt-6 rounded-2xl border p-6">
    <h2 class="text-xl font-bold">Convidar integrante</h2>
    <form action="{{ route('seller.team.invite', $seller) }}" method="post" class="mt-4 flex flex-wrap gap-3">
        @csrf
        <label class="flex-1 min-w-56">E-mail
            <input name="email" type="email" required maxlength="255" value="{{ old('email') }}" class="mt-1 block w-full rounded-xl border p-3">
        </label>
        <label class="min-w-44">Função
            <select name="role" required class="mt-1 block w-full rounded-xl border p-3">
                @foreach (['manager' => 'Gestão', 'operations' => 'Operação', 'finance' => 'Financeiro', 'support' => 'Atendimento'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="self-end rounded-xl bg-violet-700 px-5 py-3 font-bold text-white">Enviar convite</button>
    </form>
</section>

<section class="mt-6 rounded-2xl border p-6">
    <h2 class="text-xl font-bold">Integrantes</h2>
    <ul class="mt-3 space-y-3">
    @foreach ($members as $member)
        <li class="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
            <span>{{ $member->user->name }} — {{ $member->user->email }} ({{ $member->role }} / {{ $member->status }})</span>
            @if ($member->role !== 'owner' && $member->status === 'active')
                <form method="post" action="{{ route('seller.team.remove', [$seller, $member]) }}">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-red-300 px-3 py-2 text-red-700">Revogar acesso</button>
                </form>
            @endif
        </li>
    @endforeach
    </ul>
</section>

<section class="mt-6 rounded-2xl border p-6">
    <h2 class="text-xl font-bold">Convites pendentes</h2>
    @forelse ($invitations as $invitation)
        <div class="mt-3 flex flex-wrap justify-between gap-3 border-b pb-3">
            <span>{{ $invitation->email }} — {{ $invitation->role }} · Expira em {{ $invitation->expires_at }}</span>
            <form method="post" action="{{ route('seller.team.cancel', [$seller, $invitation->id]) }}">
                @csrf @method('DELETE')
                <button class="rounded-lg border px-3 py-2">Cancelar convite</button>
            </form>
        </div>
    @empty
        <p class="mt-3 text-slate-600">Não há convites pendentes.</p>
    @endforelse
</section>
@endsection
