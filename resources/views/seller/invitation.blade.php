@extends('layouts.app')
@section('title', 'Aceitar convite — Salada Mix')
@section('content')
<h1 class="text-3xl font-black">Convite para equipe</h1>
<p class="mt-3 text-slate-600">Você está prestes a aceitar um convite para colaborar como {{ $invitation->role }}.</p>
<form method="post" action="{{ route('seller.team.accept', ['invitation' => $invitation->id, 'token' => $token]) }}" class="mt-6">
    @csrf
    <button class="rounded-xl bg-violet-700 px-5 py-3 font-bold text-white">Aceitar convite</button>
</form>
@endsection
