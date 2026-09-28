@extends('layouts.app')
@section('title', 'Confirmar e-mail — Salada Mix')
@section('content')
<x-salada.auth-shell eyebrow="Mais um passo" title="Confirme seu e-mail" subtitle="Enviamos uma mensagem de verificação para o endereço abaixo.">
    <div class="sm-auth-email">{{ auth()->user()->email }}</div>
    <p class="sm-form-help">Abra o link recebido no e-mail para acessar sua conta e as funções de vendedor.</p>
    <form action="{{ route('verification.send') }}" method="post" class="sm-auth-form">
        @csrf
        <button class="sm-btn sm-btn-primary sm-auth-submit" type="submit">Reenviar mensagem de verificação</button>
    </form>
    <form action="{{ route('logout') }}" method="post" class="sm-auth-foot">@csrf<button type="submit" class="sm-auth-link-button">Sair desta conta</button></form>
</x-salada.auth-shell>
@endsection
