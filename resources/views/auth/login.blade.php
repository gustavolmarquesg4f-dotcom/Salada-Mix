@extends('layouts.app')
@section('title', 'Entrar — Salada Mix')
@section('content')
<x-salada.auth-shell title="Entrar na minha conta" subtitle="Acesse seu espaço e acompanhe seus dados e endereços." side-title="Seu universo, em um só lugar.">
    <form action="{{ route('login') }}" method="post" class="sm-auth-form">
        @csrf
        <label for="login-email">E-mail</label>
        <input id="login-email" name="email" type="email" autocomplete="email" inputmode="email" required maxlength="255" value="{{ old('email') }}" placeholder="voce@exemplo.com" @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
        @error('email')<small class="sm-field-error" id="login-email-error">{{ $message }}</small>@enderror
        <div class="sm-input-top"><label for="login-password">Senha</label><a href="{{ route('password.request') }}">Esqueci minha senha</a></div>
        <input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="Digite sua senha" @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
        @error('password')<small class="sm-field-error" id="login-password-error">{{ $message }}</small>@enderror
        <button class="sm-btn sm-btn-primary sm-auth-submit" type="submit">Entrar na minha conta →</button>
    </form>
    @include('auth._sso')
    <p class="sm-auth-foot">Ainda não tem uma conta? <a href="{{ route('register') }}">Cadastre-se gratuitamente</a></p>
</x-salada.auth-shell>
@endsection
