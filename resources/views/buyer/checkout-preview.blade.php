@extends('layouts.app')
@section('title', 'Resumo da sacola — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('buyer.account') }}">Minha conta</a><span>/</span><span aria-current="page">Resumo da sacola</span></nav>
<div class="sm-account-heading"><div><span class="sm-eyebrow">Minha conta</span><h1>Resumo da sacola</h1><p>Itens separados por vendedor para consulta.</p></div></div>
<div class="sm-account-notice" role="status">{{ $preview['reason'] }} Este resumo não é cobrança, reserva nem pedido.</div>
<div class="sm-summary-list">
    @forelse($preview['groups'] as $group)
        <section class="sm-account-panel">
            <h2>{{ $group['seller']['name'] }}</h2>
            @foreach($group['items'] as $line)
                <div class="sm-summary-line"><span>{{ $line['quantity'] }} × {{ $line['offer']['name'] }}</span><strong>R$ {{ number_format($line['line_total_cents'] / 100, 2, ',', '.') }}</strong></div>
            @endforeach
            <p class="sm-summary-note">Frete ainda sem cotação real.</p>
            <div class="sm-summary-total"><span>Subtotal do vendedor</span><strong>R$ {{ number_format($group['items_subtotal_cents'] / 100, 2, ',', '.') }}</strong></div>
        </section>
    @empty
        <div class="sm-account-panel sm-account-empty">Sua sacola não possui ofertas disponíveis. <a href="{{ route('storefront.search') }}">Explore os produtos</a>.</div>
    @endforelse
    @if($preview['unavailable_items_count'])
        <p class="sm-account-notice">{{ $preview['unavailable_items_count'] }} item(ns) indisponível(is) precisam ser revisados.</p>
    @endif
    <div class="sm-account-panel sm-summary-total"><span>Subtotal dos produtos</span><strong>R$ {{ number_format($preview['items_subtotal_cents'] / 100, 2, ',', '.') }}</strong></div>
    <button type="button" class="sm-btn sm-btn-primary" disabled aria-disabled="true">Pagamento indisponível nesta etapa</button>
    <a href="{{ route('buyer.addresses.index') }}" class="sm-btn sm-btn-secondary">Gerenciar endereços</a>
</div>
@endsection
