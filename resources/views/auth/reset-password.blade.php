@extends('layouts.app')
@section('title', 'Definir nova senha — Salada Mix')
@section('content')
<x-salada.auth-shell eyebrow="Acesso à conta" title="Defina uma nova senha" subtitle="Escolha uma senha forte e confirme para recuperar seu acesso.">
    <form action="{{ route('password.update') }}" method="post" class="sm-auth-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="reset-email">E-mail</label>
        <input id="reset-email" name="email" type="email" required autocomplete="email" value="{{ old('email', $email) }}">
        <label for="reset-password">Nova senha</label>
        <input id="reset-password" name="password" type="password" required autocomplete="new-password">
        <label for="reset-confirm">Confirmar nova senha</label>
        <input id="reset-confirm" name="password_confirmation" type="password" required autocomplete="new-password">
        <button class="sm-btn sm-btn-primary sm-auth-submit" type="submit">Alterar minha senha →</button>
    </form>
    <p class="sm-auth-foot"><a href="{{ route('login') }}">Voltar para o login</a></p>
</x-salada.auth-shell>
@endsection
