@extends('layouts.app')
@section('title', 'Checkout sandbox — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('demo.index') }}">Demonstração</a><span>/</span><span>Checkout sandbox</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">HML · E11 + E12 · SEM TRANSPORTADORA OU COBRANÇA REAL</span>
    <h1>Frete, reserva e checkout de homologação</h1>
    <p>Esta jornada usa o Laravel e o MariaDB reais da HML. O cálculo logístico é um algoritmo SANDBOX baseado em peso, dimensões, origem e CEP; não representa preço ou prazo de transportadora.</p>
    <div class="sm-preview-actions mt-4">
        <a class="sm-btn sm-btn-secondary" href="{{ route('storefront.search') }}">Escolher produtos DEMO</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('buyer.cart.page') }}">Minha sacola</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('demo.orders') }}">Pedidos sandbox</a>
    </div>
</section>

<section class="sm-account-panel mt-6">
    <span class="sm-eyebrow">Destino</span><h2>Endereço sintético</h2>
    @if($address)
        <p>{{ $address->street }}, {{ $address->number }} · {{ $address->city }}/{{ $address->state }} · CEP {{ $address->postal_code }}</p>
    @else
        <p>Endereço demonstrativo não encontrado.</p>
    @endif
</section>

<section class="sm-account-panel mt-6">
    <span class="sm-eyebrow">Sacola</span><h2>Produtos disponíveis</h2>
    @forelse($snapshot['items'] as $line)
        <article class="sm-shopping-line">
            <div class="sm-shopping-info"><strong>{{ $line['offer']['name'] ?? 'Item indisponível' }}</strong><p>{{ $line['offer']['seller']['name'] ?? 'Sem vendedor' }} · quantidade {{ $line['quantity'] }}</p></div>
            <strong>@if($line['available']) R$ {{ number_format($line['line_total_cents']/100,2,',','.') }} @else Indisponível @endif</strong>
        </article>
    @empty
        <p>Sua sacola está vazia. <a href="{{ route('storefront.search') }}">Adicionar produtos DEMO</a>.</p>
    @endforelse
    <p class="mt-3"><strong>Subtotal: R$ {{ number_format($snapshot['subtotal_cents']/100,2,',','.') }}</strong></p>
</section>

@if($shippingError)
<div class="sm-notice error mt-6" role="alert"><strong>Frete sandbox indisponível:</strong> {{ $shippingError }}</div>
@endif

@if($shipping)
<form method="post" action="{{ route('demo.create') }}" class="mt-6 grid gap-5">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    @foreach($shipping['groups'] as $group)
    <section class="sm-account-panel">
        <span class="sm-eyebrow">Cotação por vendedor</span>
        <h2>{{ $group['seller']['name'] }}</h2>
        <p class="text-sm text-slate-600">Selecione um serviço de teste. Cada opção expira em 15 minutos.</p>
        <div class="mt-4 grid gap-3">
        @foreach($group['quotes'] as $quote)
            <label class="sm-checkout-address">
                <input type="radio" name="quotes[{{ $group['seller']['id'] }}]" value="{{ $quote['id'] }}" required @checked($loop->first)>
                <span class="sm-checkout-address-content">
                    <strong>{{ $quote['name'] }}</strong>
                    <span>R$ {{ number_format($quote['amount_cents']/100,2,',','.') }} · {{ $quote['estimated_days'] }} dia(s) SANDBOX</span>
                    <small>Não é cotação ou SLA de transportadora real.</small>
                </span>
            </label>
        @endforeach
        </div>
    </section>
    @endforeach
    <section class="sm-account-panel">
        <h2>Criar pedido e reservar estoque</h2>
        <p>Ao continuar, o sistema fará uma reserva transacional do estoque DEMO. Nenhum cartão, PIX, banco ou transportadora será acionado.</p>
        <button type="submit" class="sm-btn sm-btn-primary mt-4">Reservar e criar pedido SANDBOX →</button>
    </section>
</form>
@elseif(count($snapshot['items']))
<div class="sm-notice info mt-6">Revise os produtos e os dados logísticos para liberar a cotação SANDBOX.</div>
@endif
@endsection
