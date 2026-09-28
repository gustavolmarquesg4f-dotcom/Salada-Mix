@extends('layouts.app')
@section('title', 'Verificar e-mail — Salada Mix')
@section('content')
<div class="mx-auto max-w-lg rounded-2xl border bg-white p-7">
    <h1 class="text-2xl font-black">Confirme seu e-mail</h1>
    <p class="mt-3 text-slate-600">Enviamos uma mensagem para {{ auth()->user()->email }}. Acesse o link antes de cadastrar sua empresa.</p>
    <form action="{{ route('verification.send') }}" method="post" class="mt-6">@csrf<button class="rounded-xl bg-teal-700 p-3 font-bold text-white">Reenviar verificação</button></form>
</div>
@endsection

