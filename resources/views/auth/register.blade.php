@extends('layouts.app')
@section('title', 'Criar conta — Salada Mix')
@section('content')
<form action="{{ route('register') }}" method="post" class="mx-auto max-w-md space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <h1 class="text-2xl font-black">Criar conta</h1>
    <label class="block text-sm font-semibold">Nome <input class="mt-1 w-full rounded-lg border p-3" name="name" required maxlength="160" value="{{ old('name') }}" autocomplete="name"></label>
    <label class="block text-sm font-semibold">E-mail <input class="mt-1 w-full rounded-lg border p-3" type="email" name="email" required value="{{ old('email') }}" autocomplete="email"></label>
    <label class="block text-sm font-semibold">Senha <input class="mt-1 w-full rounded-lg border p-3" name="password" type="password" required autocomplete="new-password"></label>
    <label class="block text-sm font-semibold">Confirmar senha <input class="mt-1 w-full rounded-lg border p-3" name="password_confirmation" type="password" required autocomplete="new-password"></label>
    <p class="text-xs text-slate-500">Antes de produção, termos e política de privacidade aprovados serão vinculados a este fluxo.</p>
    <button class="w-full rounded-xl bg-teal-700 p-3 font-bold text-white" type="submit">Criar conta</button>
</form>
@endsection

