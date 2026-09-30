@extends('layouts.app')
@section('title', 'Minha empresa — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><span>Portal da empresa</span></nav>
    <div class="sm-portal-heading">
        <div><span class="sm-portal-overline">Portal do vendedor</span><h1>{{ $seller->trade_name }}</h1><p>{{ $seller->legal_name }} · Gerencie catálogo, logística e acessos da sua equipe em um só lugar.</p></div>
        <span class="sm-portal-badge">Cadastro: {{ $seller->status }}</span>
    </div>
    <div class="sm-notice info"><strong>Prontidão comercial:</strong> a empresa pode organizar sua operação aqui, mas vendas reais dependem dos gates de logística, pagamento e homologação.</div>
    <div class="sm-portal-grid">
        <a class="sm-portal-card sm-portal-link-card" href="{{ route('seller.offers.index', $seller) }}"><span class="sm-portal-card-icon">▦</span><h2>Produtos e catálogo</h2><p>Cadastre produtos, imagens, preços, estoque e acompanhe a moderação.</p><strong>Gerenciar produtos →</strong></a>
        <a class="sm-portal-card sm-portal-link-card" href="{{ route('seller.origins.index', $seller) }}"><span class="sm-portal-card-icon">⌖</span><h2>Origens de envio</h2><p>Cadastre os locais usados na cotação logística de cada pedido.</p><strong>Gerenciar origens →</strong></a>
        <a class="sm-portal-card sm-portal-link-card" href="{{ route('seller.team.index', $seller) }}"><span class="sm-portal-card-icon">◎</span><h2>Equipe e permissões</h2><p>Convide integrantes e controle quem pode operar sua loja.</p><strong>Gerenciar equipe →</strong></a>
        <div class="sm-portal-card"><span class="sm-portal-card-icon">✓</span><h2>Próximos passos</h2><p>Conclua catálogo, origem de envio e revisão da empresa para avançar na homologação comercial.</p></div>
    </div>
</div>
@endsection
