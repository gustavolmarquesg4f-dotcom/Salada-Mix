@extends('layouts.app')
@section('title', 'Checkout demonstrativo — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('demo.index') }}">Demonstração</a><span>/</span><span>Checkout de teste</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">HOMOLOGAÇÃO ISOLADA · DADOS SINTÉTICOS</span>
    <h1>Monte seu pedido de demonstração</h1>
    <p>Use o catálogo e a sacola reais do Laravel para testar a jornada. O frete de R$ 9,90 por vendedor é um valor exclusivamente fictício; não há transportadora, reserva de estoque ou cobrança.</p>
    <div class="sm-preview-actions mt-4">
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Escolher produtos demonstrativos</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.cart.page') }}">Gerenciar sacola</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('demo.orders') }}">Meus pedidos fictícios</a>
    </div>
</section>
<section class="sm-account-panel mt-6">
    <h2>Endereço sintético</h2>
    @if($address)<p>{{ $address->street }}, {{ $address->number }} · {{ $address->city }}/{{ $address->state }} · CEP {{ $address->postal_code }} (fictício)</p>@else<p>Endereço demonstrativo não encontrado.</p>@endif
</section>
<section class="sm-account-panel mt-6">
    <h2>Produtos na sua sacola</h2>
    @forelse($snapshot['items'] as $line)
        <article class="sm-shopping-line">
            <div class="sm-shopping-info"><strong>{{ $line['offer']['name'] ?? 'Item indisponível' }}</strong><p>Quantidade: {{ $line['quantity'] }} · {{ $line['offer']['seller']['name'] ?? 'Sem vendedor' }}</p></div>
            <strong>@if($line['available']) R$ {{ number_format($line['line_total_cents']/100,2,',','.') }} @else Indisponível @endif</strong>
        </article>
    @empty
        <p>Sua sacola está vazia. <a href="{{ route('storefront.search') }}">Adicionar produtos DEMO</a>.</p>
    @endforelse
    <p><strong>Subtotal sintético: R$ {{ number_format($snapshot['subtotal_cents']/100,2,',','.') }}</strong></p>
    <p>O valor de entrega fictício será calculado por vendedor no pedido demonstrativo.</p>
    @if(count($snapshot['items']) && collect($snapshot['items'])->every(fn ($line) => $line['available']))
        <form method="post" action="{{ route('demo.create') }}">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
            <button type="submit" class="sm-btn sm-btn-primary">Gerar pedido fictício, sem cobrança →</button>
        </form>
    @else
        <p class="sm-notice info">Adicione produtos disponíveis para liberar a simulação do pedido.</p>
    @endif
</section>
@endsection
