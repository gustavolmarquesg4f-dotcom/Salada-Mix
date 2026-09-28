@extends('layouts.app')
@section('title', 'Minha sacola — Salada Mix')
@section('content')
<nav class="sm-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('buyer.account') }}">Minha conta</a><span>/</span><span aria-current="page">Minha sacola</span></nav>
<div class="sm-shopping-title"><div><span class="sm-eyebrow">Seu mix, suas escolhas</span><h1>Minha sacola <span>({{ count($snapshot['items']) }})</span></h1><p>Itens salvos por vendedor. Os valores e a disponibilidade são revalidados pelo servidor.</p></div><a class="sm-btn sm-btn-secondary" href="{{ route('buyer.wishlist.page') }}">♡ Meus favoritos</a></div>
<div class="sm-shopping-alert" role="status"><strong>Etapa de preparação:</strong> adicionar à sacola não reserva estoque, não gera pedido nem realiza pagamento. Frete e total final ainda indisponíveis.</div>
@if(count($snapshot['items']) === 0)
    <section class="sm-shopping-empty"><span class="sm-empty-symbol" aria-hidden="true">◇</span><h2>Sua sacola está esperando seus achados.</h2><p>Explore os departamentos e salve os produtos de que gostar.</p><a class="sm-btn sm-btn-primary" href="{{ route('storefront.search') }}">Explorar produtos →</a></section>
@else
<div class="sm-shopping-layout">
    <div class="sm-shopping-sellers">
        @foreach($groups as $sellerId => $lines)
        <section class="sm-seller-group" aria-label="Itens da loja {{ $lines->first()['offer']['seller']['name'] }}">
            <div class="sm-seller-group-header"><span class="sm-seller-dot" aria-hidden="true"></span><strong>Vendido por {{ $lines->first()['offer']['seller']['name'] }}</strong><span>{{ $lines->count() }} {{ $lines->count() === 1 ? 'produto' : 'produtos' }}</span></div>
            @foreach($lines as $line)
            @php($offer = $line['offer'])
            @php($photo = \App\Support\DemoMedia::productSlug($offer['slug']))
            <article class="sm-shopping-line">
                <a href="{{ $offer['url'] }}" class="sm-shopping-thumb" aria-label="Ver {{ $offer['name'] }}">
                    @if($photo)<img src="{{ $photo }}" alt="Imagem ilustrativa de {{ $offer['name'] }}" loading="lazy" width="112" height="112" referrerpolicy="no-referrer"><span>DEMO</span>@else<span aria-hidden="true">◇</span>@endif
                </a>
                <div class="sm-shopping-info"><a class="sm-shopping-product" href="{{ $offer['url'] }}">{{ $offer['name'] }}</a><p>{{ $offer['category']['name'] }}</p><span class="sm-shopping-availability">Disponível para consulta</span>
                    <button class="sm-shopping-remove" type="button" data-sm-commerce="cart-remove" data-url="{{ route('bff.cart.destroy', ['offer' => $line['offer_id']]) }}">Remover da sacola</button>
                </div>
                <div class="sm-shopping-numbers"><strong>R$ {{ number_format($line['line_total_cents'] / 100, 2, ',', '.') }}</strong><small>R$ {{ number_format($offer['price_cents'] / 100, 2, ',', '.') }} por unidade</small>
                    <label for="sm-qty-{{ $line['offer_id'] }}" class="sr-only">Quantidade de {{ $offer['name'] }}</label>
                    <select id="sm-qty-{{ $line['offer_id'] }}" class="sm-shopping-qty" data-sm-cart-qty data-url="{{ route('bff.cart.put', ['offer' => $line['offer_id']]) }}" data-original="{{ $line['quantity'] }}">
                        @for($q = 1; $q <= 20; $q++)<option value="{{ $q }}" @selected($q === $line['quantity'])>{{ $q }} {{ $q === 1 ? 'unidade' : 'unidades' }}</option>@endfor
                    </select>
                </div>
            </article>
            @endforeach
        </section>
        @endforeach
        @if($unavailable->isNotEmpty())
        <section class="sm-seller-group sm-unavailable" aria-labelledby="sm-unavailable-title"><h2 id="sm-unavailable-title">Itens indisponíveis</h2><p>Estes anúncios não estão disponíveis na quantidade escolhida ou deixaram de ser públicos. Você pode removê-los da sacola.</p>
            @foreach($unavailable as $line)
            <div class="sm-unavailable-line"><span>Oferta indisponível · {{ $line['quantity'] }} unidade(s) solicitada(s)</span><button class="sm-shopping-remove" data-sm-commerce="cart-remove" data-url="{{ route('bff.cart.destroy', ['offer' => $line['offer_id']]) }}" type="button">Remover</button></div>
            @endforeach
        </section>
        @endif
    </div>
    <aside class="sm-shopping-summary" aria-label="Resumo da sacola"><span class="sm-eyebrow">Resumo dos produtos</span><h2>Seu mix até agora</h2><div class="sm-shopping-total"><span>Subtotal dos itens disponíveis</span><strong>R$ {{ number_format($snapshot['subtotal_cents'] / 100, 2, ',', '.') }}</strong></div><p>Frete: aguardando cálculo real.</p><p>Total final: indisponível.</p><button type="button" disabled aria-disabled="true" class="sm-btn sm-btn-primary sm-shopping-disabled">Finalizar compra indisponível</button><a href="{{ route('buyer.checkout.preview') }}" class="sm-btn sm-btn-secondary">Ver resumo por vendedor</a><small>Não há cobrança ou reserva de estoque nesta tela.</small></aside>
</div>
@endif
@endsection
