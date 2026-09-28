@extends('layouts.app')
@section('title', 'Entrar — Salada Mix')
@section('content')
<form action="{{ route('login') }}" method="post" class="mx-auto max-w-md space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <h1 class="text-2xl font-black">Entrar na minha conta</h1>
    <label class="block text-sm font-semibold">E-mail <input class="mt-1 w-full rounded-lg border p-3" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></label>
    <label class="block text-sm font-semibold">Senha <input class="mt-1 w-full rounded-lg border p-3" name="password" type="password" autocomplete="current-password" required></label>
    <button class="w-full rounded-xl bg-teal-700 p-3 font-bold text-white" type="submit">Entrar</button>
    <div class="flex justify-between text-sm"><a href="{{ route('register') }}" class="text-teal-700">Criar conta</a><a href="{{ route('password.request') }}" class="text-teal-700">Esqueci a senha</a></div>
</form>
@endsection

