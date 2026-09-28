@extends('layouts.app')
@section('title', 'Nova senha — Salada Mix')
@section('content')
<form action="{{ route('password.update') }}" method="post" class="mx-auto max-w-md space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <h1 class="text-2xl font-black">Definir nova senha</h1>
    <label class="block text-sm font-semibold">E-mail <input name="email" type="email" required value="{{ old('email', $email) }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Nova senha <input name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Confirme a senha <input name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-lg border p-3"></label>
    <button class="w-full rounded-xl bg-teal-700 p-3 font-bold text-white">Alterar senha</button>
</form>
@endsection

