@extends('layouts.app')
@section('title', 'Minha conta — Salada Mix')
@section('content')
@php
    $user = auth()->user();
    $identities = $user->socialIdentities()->orderBy('provider')->get();
    $providers = collect(config('sso.providers', []))->filter(fn ($provider) => (bool) ($provider['enabled'] ?? false));
@endphp
<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span>/</span><span aria-current="page">Minha conta</span></nav>
<div class="sm-account-heading">
    <div><span class="sm-eyebrow">Seu espaço no Salada Mix</span><h1>Olá, {{ $user->name }}!</h1><p>Organize seus dados e acompanhe suas preferências em um só lugar.</p></div>
    <span class="sm-account-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}</span>
</div>
<div class="sm-account-notice" role="status">
    @if($hmlSandbox)
        <strong>Homologação ativa:</strong> sua conta, endereços, sacola e frete SANDBOX podem ser testados normalmente. Nenhuma cobrança ou transportadora real é acionada.
    @else
        <strong>Loja em preparação:</strong> carrinho e resumo são apenas consulta; o pagamento real ainda não está habilitado.
    @endif
</div>

<section class="sm-account-overview" aria-labelledby="sm-account-overview-title">
    <div class="sm-account-overview-head">
        <div><span class="sm-eyebrow">Visão rápida</span><h2 id="sm-account-overview-title">Resumo da conta</h2></div>
        @if($hmlSandbox)<span class="sm-account-chip">HML SANDBOX</span>@endif
    </div>
    <div class="sm-account-overview-grid">
        <a class="sm-account-overview-card" href="{{ route('buyer.addresses.index') }}">
            <span class="sm-account-overview-icon"><x-salada.icon name="pin" size="22" /></span>
            <strong>{{ $accountSummary['addresses'] }}</strong>
            <span>{{ $accountSummary['addresses'] === 1 ? 'endereço salvo' : 'endereços salvos' }}</span>
            <small>Gerenciar entrega →</small>
        </a>
        <a class="sm-account-overview-card" href="{{ route('buyer.cart.page') }}">
            <span class="sm-account-overview-icon"><x-salada.icon name="cart" size="22" /></span>
            <strong>{{ $accountSummary['cart_items'] }}</strong>
            <span>{{ $accountSummary['cart_items'] === 1 ? 'item na sacola' : 'itens na sacola' }}</span>
            <small>Subtotal R$ {{ number_format($accountSummary['cart_subtotal_cents'] / 100, 2, ',', '.') }} →</small>
        </a>
        <a class="sm-account-overview-card" href="{{ route('buyer.wishlist.page') }}">
            <span class="sm-account-overview-icon"><x-salada.icon name="grid" size="22" /></span>
            <strong>{{ $accountSummary['wishlist_items'] }}</strong>
            <span>{{ $accountSummary['wishlist_items'] === 1 ? 'favorito salvo' : 'favoritos salvos' }}</span>
            <small>Ver favoritos →</small>
        </a>
        <a class="sm-account-overview-card sm-account-overview-shipping" href="{{ route('buyer.checkout.prepare') }}">
            <span class="sm-account-overview-icon"><x-salada.icon name="truck" size="22" /></span>
            <strong>{{ $hmlSandbox ? 'Frete SANDBOX' : 'Frete e entrega' }}</strong>
            <span>{{ $hmlSandbox ? 'Cotação por CEP, peso e loja' : 'Preparar endereço e entrega' }}</span>
            <small>{{ $hmlSandbox ? 'Calcular na HML →' : 'Preparar compra →' }}</small>
        </a>
    </div>
    @if($isDemoBuyer)
        <div class="sm-account-demo-shortcut">
            <div><strong>Você está usando um comprador de demonstração.</strong><span>Pedidos, pagamento e pós-venda SANDBOX ficam isolados da operação real.</span></div>
            <a class="sm-btn sm-btn-secondary" href="{{ route('demo.orders') }}">Meus pedidos SANDBOX →</a>
        </div>
    @endif
</section>

<div class="sm-account-layout">
    <nav class="sm-account-nav" aria-label="Minha conta">
        <a href="#dados" aria-current="page">Meus dados</a>
        <a href="{{ route('buyer.addresses.index') }}">Endereços</a>
        <a href="{{ route('buyer.cart.page') }}">Minha sacola</a>
        <a href="{{ route('buyer.wishlist.page') }}">Meus favoritos</a>
        <a href="{{ route('buyer.checkout.prepare') }}">Frete e entrega</a>
        <a href="{{ route('buyer.checkout.preview') }}">Resumo da sacola</a>
        <a href="#seguranca">Login e segurança</a>
        <a href="#sessoes">Dispositivos conectados</a>
        @can('review-sellers')<a href="{{ route('security.mfa.show') }}">Segurança administrativa</a>@endcan
        <form action="{{ route('logout') }}" method="post">@csrf<button type="submit">Sair da conta</button></form>
    </nav>
    <div class="sm-account-panels">
        <section class="sm-account-panel" id="dados" aria-labelledby="sm-account-data-title">
            <div class="sm-panel-top"><div><span class="sm-eyebrow">Dados pessoais</span><h2 id="sm-account-data-title">Minhas informações</h2></div><span class="sm-account-chip">{{ $user->hasVerifiedEmail() ? 'E-mail verificado' : 'Verificação pendente' }}</span></div>
            <p>Para alterar seu e-mail, confirme a senha atual ou faça uma autenticação SSO recente. O novo endereço precisará de verificação.</p>
            <form class="sm-account-form" data-sm-bff data-method="PATCH" data-next-on-email="{{ route('verification.notice') }}" data-initial-email="{{ $user->email }}" data-url="{{ route('bff.account.update') }}">
                <label for="profile-name">Nome completo</label>
                <input id="profile-name" name="name" value="{{ $user->name }}" maxlength="160" autocomplete="name" required>
                <label for="profile-email">E-mail</label>
                <input id="profile-email" name="email" value="{{ $user->email }}" type="email" maxlength="255" autocomplete="email" required>
                <label for="profile-confirm">Senha atual <span>(necessária para alterar e-mail por senha)</span></label>
                <input id="profile-confirm" name="current_password" type="password" autocomplete="current-password" placeholder="Opcional quando apenas o nome muda">
                <button class="sm-btn sm-btn-primary" type="submit" disabled>Salvar alterações</button>
                <p class="sm-form-help">O formulário utiliza sua sessão segura no mesmo domínio. Habilite JavaScript para gerenciar seus dados.</p>
                <div class="sm-bff-feedback" role="status" aria-live="polite" hidden></div>
            </form>
        </section>
        <section class="sm-account-panel" id="seguranca" aria-labelledby="sm-account-security-title">
            <div class="sm-panel-top"><div><span class="sm-eyebrow">Proteja seu acesso</span><h2 id="sm-account-security-title">Login e segurança</h2></div><span class="sm-account-chip">Sua conta</span></div>
            <p>Senha: <strong>{{ $user->password_login_enabled ? 'habilitada' : 'ainda não definida' }}</strong>. Nunca compartilhe códigos ou senhas.</p>
            <div class="sm-linked-accounts">
                @forelse($identities as $identity)
                    <div class="sm-linked-account"><div><strong>{{ config("sso.providers.{$identity->provider}.label", ucfirst($identity->provider)) }}</strong><small>{{ $identity->provider_email }}</small></div><span class="sm-account-chip">Conectado</span></div>
                @empty
                    <p>Nenhum provedor externo conectado.</p>
                @endforelse
            </div>
            @if($providers->isNotEmpty())
                <div class="sm-provider-actions">
                    @foreach($providers as $id => $provider)
                        @if(!$identities->contains('provider', $id))
                            <a class="sm-btn sm-btn-secondary" href="{{ route('sso.redirect', $id) }}">Conectar {{ $provider['label'] ?? ucfirst($id) }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
            <form class="sm-account-form" data-sm-bff data-method="PUT" data-url="{{ $user->password_login_enabled ? route('bff.account.password') : route('bff.auth.password.establish') }}">
                <h3>{{ $user->password_login_enabled ? 'Trocar senha' : 'Criar senha para esta conta' }}</h3>
                @if($user->password_login_enabled)
                    <label for="security-current">Senha atual</label><input id="security-current" name="current_password" type="password" autocomplete="current-password" required>
                @else
                    <p class="sm-form-help">Para criar senha em uma conta SSO, autentique-se novamente pelo provedor conectado antes de prosseguir.</p>
                @endif
                <label for="security-new">Nova senha</label><input id="security-new" name="password" type="password" autocomplete="new-password" required>
                <label for="security-confirm">Confirme a nova senha</label><input id="security-confirm" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="sm-btn sm-btn-primary" type="submit" disabled>{{ $user->password_login_enabled ? 'Atualizar senha' : 'Definir minha senha' }}</button>
                <div class="sm-bff-feedback" role="status" aria-live="polite" hidden></div>
            </form>
        </section>
        <section class="sm-account-panel" id="sessoes" aria-labelledby="sm-sessions-title">
            <div class="sm-panel-top"><div><span class="sm-eyebrow">Controle de acesso</span><h2 id="sm-sessions-title">Dispositivos conectados</h2></div></div>
            <p>Consulte as sessões da sua conta. O gerenciamento requer sessões armazenadas no banco de dados.</p>
            <button type="button" class="sm-btn sm-btn-secondary" data-sm-session-load data-url="{{ route('bff.account.sessions') }}" disabled>Consultar dispositivos</button>
            <div class="sm-bff-feedback" id="sm-session-feedback" role="status" aria-live="polite" hidden></div>
            <div id="sm-session-list" class="sm-session-list"></div>
            <p class="sm-form-help">Para encerrar esta sessão, utilize “Sair da conta” no menu lateral.</p>
        </section>
    </div>
</div>
<noscript><p class="sm-notice error">Para alterar os dados desta conta, habilite JavaScript. A navegação e o acesso às páginas de endereço continuam disponíveis.</p></noscript>
@endsection
