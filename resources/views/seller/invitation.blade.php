@extends('layouts.app')
@section('title', 'Aceitar convite — Salada Mix')
@section('content')
<div class="sm-portal-shell"><div class="sm-portal-card sm-centered-card"><span class="sm-portal-overline">Equipe do vendedor</span><h1>Convite para colaborar</h1><p>Você foi convidado para atuar como <strong>{{ $invitation->role }}</strong>. Confirme para vincular sua conta à equipe da loja.</p><form method="post" action="{{ route('seller.team.accept', ['invitation' => $invitation->id, 'token' => $token]) }}" class="mt-5">@csrf<button class="sm-btn sm-btn-primary">Aceitar convite →</button></form></div></div>
@endsection
