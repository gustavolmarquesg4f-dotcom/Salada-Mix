@extends('layouts.app')
@section('title', 'Recuperar senha — Salada Mix')
@section('content')
<form action="{{ route('password.email') }}" method="post" class="mx-auto max-w-md space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <h1 class="text-2xl font-black">Recuperar senha</h1>
    <p class="text-sm text-slate-600">Se sua conta existir, enviaremos as instruções para o e-mail informado.</p>
    <label class="block text-sm font-semibold">E-mail <input name="email" type="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <button class="w-full rounded-xl bg-teal-700 p-3 font-bold text-white">Enviar instruções</button>
</form>
@endsection

