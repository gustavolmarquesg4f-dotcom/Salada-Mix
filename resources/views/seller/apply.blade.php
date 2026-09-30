@extends('layouts.app')
@section('title', 'Cadastrar empresa — Salada Mix')
@section('content')
<div class="sm-portal-shell sm-seller-apply-shell">
    <div class="sm-seller-apply-intro"><span class="sm-portal-overline">Venda no Salada Mix</span><h1>Leve sua loja para um marketplace feito para vários estilos.</h1><p>Cadastre os dados básicos da empresa. Depois da análise, você poderá organizar catálogo, fotos, estoque, logística e equipe dentro do portal.</p><div class="sm-seller-apply-points"><span>✓ Catálogo administrável</span><span>✓ Equipe com permissões</span><span>✓ Moderação antes da publicação</span></div></div>
    <form action="{{ route('seller.submit') }}" method="post" class="sm-portal-card sm-portal-form">
        @csrf
        <h2>Dados da empresa</h2>
        <label>Razão social<input name="legal_name" required maxlength="200" value="{{ old('legal_name') }}"></label>
        <label>Nome fantasia<input name="trade_name" required maxlength="160" value="{{ old('trade_name') }}"></label>
        <label>CNPJ<input name="cnpj" inputmode="text" required maxlength="18" value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00"></label>
        <label>E-mail comercial<input name="contact_email" type="email" required maxlength="255" value="{{ old('contact_email') }}" placeholder="contato@sualoja.com.br"></label>
        <p class="sm-form-help">Não envie documentos sensíveis por este formulário. A análise documental possui fluxo próprio.</p>
        <button class="sm-btn sm-btn-primary" type="submit">Enviar empresa para análise →</button>
    </form>
</div>
@endsection
