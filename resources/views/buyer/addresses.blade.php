@extends('layouts.app')
@section('title', 'Meus endereços — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('buyer.account') }}">Minha conta</a><span>/</span><span aria-current="page">Endereços</span></nav>
<div class="sm-account-heading"><div><span class="sm-eyebrow">Minha conta</span><h1>Meus endereços</h1><p>Gerencie onde deseja receber suas futuras compras.</p></div></div>
<div class="sm-account-notice" role="status">Cadastrar um endereço não contrata frete nem cria um pedido. O checkout está desativado.</div>
<div class="sm-address-layout">
    <section class="sm-account-panel" aria-labelledby="address-list-title">
        <div class="sm-panel-top"><h2 id="address-list-title">Endereços cadastrados</h2><span class="sm-account-chip">{{ $addresses->count() }} de 10</span></div>
        @forelse($addresses as $address)
            <article class="sm-address-item">
                <div><strong>{{ $address->label }}</strong>@if($address->is_default)<span class="sm-account-chip">Principal</span>@endif</div>
                <p>{{ $address->recipient_name }}</p>
                <p>{{ $address->street }}, {{ $address->number }}@if($address->complement) — {{ $address->complement }}@endif<br>{{ $address->neighborhood }} · {{ $address->city }}/{{ $address->state }}<br>{{ substr($address->postal_code, 0, 5) }}-{{ substr($address->postal_code, 5) }}</p>
                <form action="{{ route('buyer.addresses.destroy', $address->id) }}" method="post">
                    @csrf @method('DELETE')<button type="submit" class="sm-destructive">Excluir endereço</button>
                </form>
            </article>
        @empty
            <p class="sm-account-empty">Você ainda não tem endereços salvos. Preencha o formulário ao lado para cadastrar o primeiro.</p>
        @endforelse
    </section>
    <section class="sm-account-panel" aria-labelledby="address-create-title">
        <h2 id="address-create-title">Adicionar endereço</h2>
        <p>Os dados ficam associados exclusivamente à sua conta.</p>
        <form action="{{ route('buyer.addresses.store') }}" method="post" class="sm-account-form sm-address-form">
            @csrf
            <label for="address-label">Apelido (ex.: casa ou trabalho)</label>
            <input id="address-label" name="label" value="{{ old('label') }}" required maxlength="60">
            <label for="address-name">Nome de quem recebe</label>
            <input id="address-name" name="recipient_name" value="{{ old('recipient_name') }}" required autocomplete="name">
            <label for="address-phone">Telefone <span>(opcional)</span></label>
            <input id="address-phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">
            <label for="address-cep">CEP</label>
            <input id="address-cep" name="postal_code" type="text" inputmode="numeric" maxlength="9" pattern="[0-9]{5}-?[0-9]{3}" value="{{ old('postal_code') }}" autocomplete="postal-code" placeholder="00000-000" required>
            <div class="sm-form-split"><div><label for="address-street">Rua ou avenida</label><input id="address-street" name="street" value="{{ old('street') }}" required autocomplete="address-line1"></div><div><label for="address-number">Número</label><input id="address-number" name="number" value="{{ old('number') }}" required></div></div>
            <label for="address-complement">Complemento <span>(opcional)</span></label><input id="address-complement" name="complement" value="{{ old('complement') }}" autocomplete="address-line2">
            <label for="address-neighborhood">Bairro</label><input id="address-neighborhood" name="neighborhood" value="{{ old('neighborhood') }}" required>
            <div class="sm-form-split"><div><label for="address-city">Cidade</label><input id="address-city" name="city" value="{{ old('city') }}" required autocomplete="address-level2"></div><div><label for="address-state">UF</label><input id="address-state" name="state" value="{{ old('state') }}" required maxlength="2" minlength="2" pattern="[A-Za-z]{2}" autocomplete="address-level1" placeholder="DF"></div></div>
            <label class="sm-form-check"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> Usar como endereço principal</label>
            <button type="submit" class="sm-btn sm-btn-primary">Salvar endereço</button>
            <p class="sm-form-help">O CEP é um dado cadastral; o cálculo de frete ainda será integrado.</p>
        </form>
    </section>
</div>
<div class="sm-address-back"><a href="{{ route('buyer.account') }}">← Voltar para minha conta</a></div>
@endsection
