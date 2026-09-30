@extends('layouts.app')
@section('title', 'Pedidos demonstrativos — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('demo.index') }}">Demonstração</a><span>/</span><span>Pedidos</span></nav>
<section class="sm-preview-panel"><span class="sm-eyebrow">DEMONSTRAÇÃO · SEM DINHEIRO REAL</span><h1>Meus pedidos fictícios</h1>
<p>Estes pedidos não são compras, não diminuem estoque e não geram obrigações comerciais. Os estados são persistidos em tabelas separadas do banco.</p>
<div class="sm-preview-actions"><a class="sm-btn sm-btn-primary" href="{{ route('demo.checkout') }}">Nova simulação</a><a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Explorar catálogo</a></div></section>
<section class="sm-account-panel mt-6">
@forelse($orders as $order)
    <div class="sm-checkout-seller"><strong>Pedido DEMO {{ $order->id }}</strong><p>Situação: {{ $order->status }} · Pagamento: {{ $order->payment_status }}</p>
    <p>Valor ilustrativo: R$ {{ number_format($order->grand_total_cents/100,2,',','.') }}</p><a class="sm-btn sm-btn-secondary" href="{{ route('demo.order', $order->id) }}">Abrir e acompanhar →</a></div>
@empty <p>Você ainda não gerou pedidos fictícios.</p>
@endforelse
{{ $orders->links() }}
</section>
@endsection
