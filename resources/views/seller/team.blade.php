@extends('layouts.app')
@section('title', 'Equipe — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('seller.dashboard', $seller) }}">Painel da empresa</a><span>/</span><span>Equipe</span></nav>
    <div class="sm-portal-heading"><div><span class="sm-portal-overline">Acessos da loja</span><h1>Equipe de {{ $seller->trade_name }}</h1><p>Convide integrantes e controle os acessos. Convites expiram em 48 horas.</p></div></div>
    <section class="sm-portal-card">
        <h2>Convidar integrante</h2>
        <form action="{{ route('seller.team.invite', $seller) }}" method="post" class="sm-portal-form sm-portal-form-inline">
            @csrf
            <label>E-mail<input name="email" type="email" required maxlength="255" value="{{ old('email') }}" placeholder="nome@empresa.com"></label>
            <label>Função<select name="role" required>@foreach (['manager' => 'Gestão', 'operations' => 'Operação', 'finance' => 'Financeiro', 'support' => 'Atendimento'] as $key => $label)<option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>@endforeach</select></label>
            <button class="sm-btn sm-btn-primary" type="submit">Enviar convite</button>
        </form>
    </section>
    <div class="sm-portal-grid mt-5">
        <section class="sm-portal-card"><h2>Integrantes</h2><div class="sm-portal-list">@foreach ($members as $member)<div class="sm-portal-row"><div><strong>{{ $member->user->name }}</strong><small>{{ $member->user->email }} · {{ $member->role }} · {{ $member->status }}</small></div>@if ($member->role !== 'owner' && $member->status === 'active')<form method="post" action="{{ route('seller.team.remove', [$seller, $member]) }}">@csrf @method('DELETE')<button class="sm-portal-danger">Revogar acesso</button></form>@endif</div>@endforeach</div></section>
        <section class="sm-portal-card"><h2>Convites pendentes</h2><div class="sm-portal-list">@forelse ($invitations as $invitation)<div class="sm-portal-row"><div><strong>{{ $invitation->email }}</strong><small>{{ $invitation->role }} · expira em {{ $invitation->expires_at }}</small></div><form method="post" action="{{ route('seller.team.cancel', [$seller, $invitation->id]) }}">@csrf @method('DELETE')<button class="sm-portal-danger">Cancelar</button></form></div>@empty<p class="sm-account-empty">Não há convites pendentes.</p>@endforelse</div></section>
    </div>
</div>
@endsection
