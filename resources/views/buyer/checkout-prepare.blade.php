@extends('layouts.app')
@section('title', 'Preparação de compra — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação">
    <a href="{{ route('home') }}">Início</a><span>/</span>
    <a href="{{ route('buyer.cart.page') }}">Minha sacola</a><span>/</span>
    <span aria-current="page">Preparação de compra</span>
</nav>
<div class="sm-checkout-heading">
    <span class="sm-eyebrow">Entrega integrada FE-06 + BE-06</span>
    <h1>Prepare seu pedido</h1>
    <p>Confira o endereço, os produtos e a situação de cada loja antes de uma futura compra.</p>
</div>
<div class="sm-shopping-alert" role="status"><strong>Ambiente em preparação:</strong> esta página não cria pedidos, não reserva estoque e não solicita pagamento.</div>
<ol class="sm-checkout-steps" aria-label="Etapas da preparação">
    <li class="is-done"><span>1</span> Sacola</li>
    <li class="is-current" aria-current="step"><span>2</span> Endereço</li>
    <li><span>3</span> Frete</li>
    <li><span>4</span> Pagamento</li>
</ol>
<div class="sm-checkout-layout">
    <div class="sm-checkout-main">
        <section class="sm-account-panel" aria-labelledby="sm-shipping-address">
            <div class="sm-panel-top"><div><span class="sm-eyebrow">Passo 2</span><h2 id="sm-shipping-address">Endereço de entrega</h2></div><a class="sm-checkout-edit" href="{{ route('buyer.addresses.index') }}">Gerenciar endereços →</a></div>
            @if(count($addresses) === 0)
                <div class="sm-checkout-empty"><strong>Você ainda não tem endereço cadastrado.</strong><p>Cadastre um endereço para verificar a preparação do pedido.</p><a class="sm-btn sm-btn-secondary" href="{{ route('buyer.addresses.index') }}">Cadastrar endereço</a></div>
            @else
                <div class="sm-checkout-addresses" aria-label="Selecionar endereço para visualização">
                    @foreach($addresses as $address)
                        @php($active = $selected_address && $selected_address['id'] === $address['id'])
                        <a class="sm-checkout-address {{ $active ? 'is-selected' : '' }}"
                           href="{{ route('buyer.checkout.prepare', ['address' => $address['id']]) }}"
                           @if($active) aria-current="true" @endif>
                            <span class="sm-checkout-radio" aria-hidden="true"></span>
                            <span class="sm-checkout-address-content"><strong>{{ $address['label'] }} @if($address['is_default'])<small>Principal</small>@endif</strong>
                                <span>{{ $address['recipient_name'] }}</span>
                                <span>{{ $address['street'] }}, {{ $address['number'] }}@if($address['complement']) — {{ $address['complement'] }}@endif</span>
                                <span>{{ $address['neighborhood'] }} · {{ $address['city'] }}/{{ $address['state'] }} · {{ substr($address['postal_code'], 0, 5) }}-{{ substr($address['postal_code'], 5) }}</span>
                            </span>
                            <span class="sm-checkout-select-word">{{ $active ? 'Selecionado' : 'Selecionar' }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="sm-form-help">Selecionar outro endereço altera apenas esta visualização. Nenhum frete é solicitado.</p>
            @endif
        </section>
        <section class="sm-account-panel" aria-labelledby="sm-checkout-shipping">
            <div class="sm-panel-top"><div><span class="sm-eyebrow">Passo 3</span><h2 id="sm-checkout-shipping">Entrega por vendedor</h2></div><span class="sm-account-chip">Sem cotação real</span></div>
            @forelse($preview['groups'] as $group)
                <div class="sm-checkout-seller">
                    <div class="sm-checkout-seller-head"><span class="sm-seller-dot" aria-hidden="true"></span><strong>{{ $group['seller']['name'] }}</strong><span>{{ count($group['items']) }} produto(s)</span></div>
                    <div class="sm-checkout-seller-lines">
                        @foreach($group['items'] as $line)
                        @php($image = \App\Support\DemoMedia::productSlug($line['offer']['slug']))
                        <div class="sm-checkout-line">
                            <a class="sm-checkout-photo" href="{{ $line['offer']['url'] }}" aria-label="Ver {{ $line['offer']['name'] }}">
                                @if($image)<img src="{{ $image }}" alt="Imagem ilustrativa: {{ $line['offer']['name'] }}" loading="lazy" width="76" height="76" referrerpolicy="no-referrer">@else<span aria-hidden="true">◇</span>@endif
                            </a>
                            <div><a href="{{ $line['offer']['url'] }}">{{ $line['offer']['name'] }}</a><small>{{ $line['quantity'] }} × R$ {{ number_format($line['offer']['price_cents'] / 100, 2, ',', '.') }}</small></div>
                            <strong>R$ {{ number_format($line['line_total_cents'] / 100, 2, ',', '.') }}</strong>
                        </div>
                        @endforeach
                    </div>
                    <div class="sm-checkout-shipping-state">
                        @if($group['shipping']['status'] === 'origin_not_configured')
                            <span class="sm-checkout-indicator is-warning" aria-hidden="true"></span>
                            <span>Origem de envio ainda não cadastrada pelo vendedor. Frete indisponível.</span>
                        @else
                            <span class="sm-checkout-indicator" aria-hidden="true"></span>
                            <span>Origem de envio cadastrada. A transportadora e a cotação real ainda não estão integradas.</span>
                        @endif
                    </div>
                    <div class="sm-checkout-seller-subtotal"><span>Subtotal desta loja</span><strong>R$ {{ number_format($group['items_subtotal_cents'] / 100, 2, ',', '.') }}</strong></div>
                </div>
            @empty
                <div class="sm-checkout-empty"><strong>Sua sacola ainda não tem produtos disponíveis.</strong><p>Adicione produtos antes de verificar a entrega.</p><a href="{{ route('storefront.search') }}" class="sm-btn sm-btn-secondary">Explorar produtos</a></div>
            @endforelse
            @if($preview['unavailable_items_count'] > 0)
                <div class="sm-shopping-alert">{{ $preview['unavailable_items_count'] }} item(ns) indisponível(is) não estão incluídos no subtotal. <a href="{{ route('buyer.cart.page') }}">Revisar a sacola</a>.</div>
            @endif
        </section>
    </div>
    <aside class="sm-checkout-sidebar" aria-labelledby="sm-checkout-summary">
        <span class="sm-eyebrow">Resumo da preparação</span><h2 id="sm-checkout-summary">Seu mix</h2>
        <div class="sm-checkout-price"><span>Produtos disponíveis</span><strong>R$ {{ number_format($preview['items_subtotal_cents'] / 100, 2, ',', '.') }}</strong></div>
        <div class="sm-checkout-price"><span>Frete</span><strong>Não cotado</strong></div>
        <div class="sm-checkout-price sm-checkout-grand"><span>Total final</span><strong>Indisponível</strong></div>
        <div class="sm-checkout-readiness"><h3>Pendências desta compra</h3><ul>
            @foreach($readiness['issues'] as $issue)<li>{{ $issue }}</li>@endforeach
        </ul></div>
        <button class="sm-btn sm-btn-primary sm-checkout-lock" type="button" disabled aria-disabled="true">Finalizar compra indisponível</button>
        <a href="{{ route('buyer.cart.page') }}" class="sm-btn sm-btn-secondary">← Voltar à sacola</a>
        <p>Não representa orçamento, promessa de entrega, reserva, cobrança ou pedido.</p>
    </aside>
</div>
@endsection
