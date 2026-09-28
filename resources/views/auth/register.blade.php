@extends('layouts.app')
@section('title', 'Criar conta — Salada Mix')
@section('content')
<x-salada.auth-shell eyebrow="Vamos começar?" title="Crie sua conta" subtitle="Seus dados, seus endereços e seus produtos favoritos no mesmo lugar." side-title="Tudo o que você ama, em um só lugar.">
    <form action="{{ route('register') }}" method="post" class="sm-auth-form">
        @csrf
        <label for="register-name">Nome completo</label>
        <input id="register-name" name="name" required maxlength="160" autocomplete="name" value="{{ old('name') }}" placeholder="Como podemos chamar você?" @error('name') aria-invalid="true" aria-describedby="register-name-error" @enderror>
        @error('name')<small class="sm-field-error" id="register-name-error">{{ $message }}</small>@enderror
        <label for="register-email">E-mail</label>
        <input id="register-email" name="email" type="email" inputmode="email" autocomplete="email" required maxlength="255" value="{{ old('email') }}" placeholder="voce@exemplo.com" @error('email') aria-invalid="true" aria-describedby="register-email-error" @enderror>
        @error('email')<small class="sm-field-error" id="register-email-error">{{ $message }}</small>@enderror
        <label for="register-password">Crie uma senha</label>
        <input id="register-password" name="password" type="password" required autocomplete="new-password" placeholder="Use uma senha forte" @error('password') aria-invalid="true" aria-describedby="register-password-error" @enderror>
        @error('password')<small class="sm-field-error" id="register-password-error">{{ $message }}</small>@enderror
        <label for="register-confirm">Confirme sua senha</label>
        <input id="register-confirm" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="Digite novamente a senha">
        <p class="sm-form-help">Enviaremos um link para verificar seu e-mail. Não usamos dados de cadastro para comunicação promocional sem a base legal aplicável.</p>
        <button class="sm-btn sm-btn-primary sm-auth-submit" type="submit">Criar minha conta →</button>
    </form>
    @include('auth._sso')
    <p class="sm-auth-foot">Já tem uma conta? <a href="{{ route('login') }}">Entrar</a></p>
    <p class="sm-form-help">Os links de termos e política de privacidade serão disponibilizados após aprovação jurídica dos respectivos documentos.</p>
</x-salada.auth-shell>
@endsection
