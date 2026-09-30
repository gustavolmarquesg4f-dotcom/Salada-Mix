@extends('layouts.app')
@section('title', 'Acompanhar pedido fictício — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('demo.orders') }}">Pedidos DEMO</a><span>/</span><span>Detalhes</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">SIMULAÇÃO CONTROLADA · NÃO É UMA COMPRA</span>
    <h1>Pedido fictício {{ $record->id }}</h1>
    <p>Etapa: <strong>{{ $record->status }}</strong> · Pagamento: <strong>{{ $record->payment_status }}</strong>. Este fluxo não se comunica com bancos, processadoras, transportadoras ou estoque comercial.</p>
</section>
<section class="sm-account-panel mt-6"><h2>Itens do pedido</h2>
@foreach($items as $item)
<div class="sm-shopping-line"><div><strong>{{ $item->name_snapshot }}</strong><p>{{ $item->seller_snapshot }} · SKU {{ $item->sku_snapshot }} · {{ $item->quantity }} un.</p></div><strong>R$ {{ number_format($item->line_total_cents/100,2,',','.') }}</strong></div>
@endforeach
<p>Produtos: R$ {{ number_format($record->items_total_cents/100,2,',','.') }}</p>
<p>Entrega simulada (R$ 9,90 por vendedor): R$ {{ number_format($record->shipping_total_cents/100,2,',','.') }}</p>
<h3>Total ilustrativo: R$ {{ number_format($record->grand_total_cents/100,2,',','.') }}</h3>
</section>
<section class="sm-account-panel mt-6"><h2>Simular etapas do atendimento</h2>
<p>Os botões abaixo alteram somente o estado deste pedido fictício no banco. Não iniciam pagamento nem entrega.</p>
<div class="sm-preview-actions">
@foreach(['pay'=>'Simular pagamento aprovado','prepare'=>'Simular separação','ship'=>'Simular postagem','deliver'=>'Simular entrega','cancel'=>'Cancelar pedido fictício'] as $action => $label)
@php($allowed = match($action) {'pay','cancel' => $record->status === 'created_demo', 'prepare' => $record->status === 'paid_demo', 'ship' => $record->status === 'preparing_demo', 'deliver' => $record->status === 'shipped_demo', default => false})
@if($allowed)<form method="post" action="{{ route('demo.transition', $record->id) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button class="sm-btn sm-btn-secondary" type="submit">{{ $label }}</button></form>@endif
@endforeach
</div>
</section>
<section class="sm-account-panel mt-6"><h2>Histórico auditável da simulação</h2>
@foreach($events as $event)<p>{{ $event->created_at }} — {{ $event->event_type }}</p>@endforeach
<a class="sm-btn sm-btn-secondary" href="{{ route('demo.orders') }}">← Meus pedidos fictícios</a></section>
@endsection
