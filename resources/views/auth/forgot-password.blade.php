@extends('layouts.app')
@section('title', 'Recuperar senha — Salada Mix')
@section('content')
<x-salada.auth-shell eyebrow="Acesso à conta" title="Esqueceu sua senha?" subtitle="Informe o e-mail cadastrado. Se existir uma conta, enviaremos instruções para redefinir a senha.">
    <form action="{{ route('password.email') }}" method="post" class="sm-auth-form">
        @csrf
        <label for="forgot-email">E-mail da conta</label>
        <input id="forgot-email" name="email" type="email" autocomplete="email" required maxlength="255" value="{{ old('email') }}" placeholder="voce@exemplo.com">
        <button class="sm-btn sm-btn-primary sm-auth-submit" type="submit">Enviar instruções →</button>
    </form>
    <p class="sm-auth-foot"><a href="{{ route('login') }}">← Voltar para o login</a></p>
</x-salada.auth-shell>
@endsection
