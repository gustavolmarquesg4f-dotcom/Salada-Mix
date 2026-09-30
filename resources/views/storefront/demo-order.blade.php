@extends('layouts.app')
@section('title', 'Pedido sandbox — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('demo.orders') }}">Pedidos SANDBOX</a><span>/</span><span>Detalhes</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">HML · E12 + E13 · ZERO COBRANÇA EXTERNA</span>
    <h1>Pedido SANDBOX {{ $record->id }}</h1>
    <p>Pedido: <strong>{{ $record->status }}</strong> · pagamento: <strong>{{ $record->payment_status }}</strong>.</p>
    @if($record->expires_at && $record->status === 'created_demo')<p>Reserva válida até {{ $record->expires_at }}.</p>@endif
</section>

<section class="sm-account-panel mt-6">
    <h2>Itens e reserva de estoque</h2>
    @foreach($items as $item)
    <div class="sm-shopping-line"><div><strong>{{ $item->name_snapshot }}</strong><p>{{ $item->seller_snapshot }} · SKU {{ $item->sku_snapshot }} · {{ $item->quantity }} un.</p></div><strong>R$ {{ number_format($item->line_total_cents/100,2,',','.') }}</strong></div>
    @endforeach
    <div class="mt-4 grid gap-2">
        @foreach($reservations as $reservation)<p>Reserva {{ $reservation->offer_id }}: <strong>{{ $reservation->status }}</strong> · {{ $reservation->quantity }} un.</p>@endforeach
    </div>
</section>

<section class="sm-account-panel mt-6">
    <h2>Frete escolhido por loja</h2>
    @foreach($shipments as $shipment)
    <div class="sm-checkout-seller mt-3 p-3"><strong>{{ $shipment->service_name }}</strong><p>R$ {{ number_format($shipment->amount_cents/100,2,',','.') }} · {{ $shipment->estimated_days }} dia(s) SANDBOX · CEP {{ $shipment->destination_postal_code }}</p></div>
    @endforeach
    <div class="mt-4"><p>Produtos: R$ {{ number_format($record->items_total_cents/100,2,',','.') }}</p><p>Frete SANDBOX: R$ {{ number_format($record->shipping_total_cents/100,2,',','.') }}</p><h3>Total: R$ {{ number_format($record->grand_total_cents/100,2,',','.') }}</h3></div>
</section>

@if($record->status === 'created_demo')
<section class="sm-account-panel mt-6">
    <span class="sm-eyebrow">Pagamento sandbox</span><h2>Testar aprovação ou recusa</h2>
    <p>Os botões abaixo registram uma tentativa idempotente apenas no banco da HML. Não existe adquirente, cartão ou PIX conectado.</p>
    <div class="sm-preview-actions mt-4">
        <form method="post" action="{{ route('demo.payment', $record->id) }}">@csrf<input type="hidden" name="outcome" value="approve"><input type="hidden" name="idempotency_key" value="{{ $paymentKey }}"><button class="sm-btn sm-btn-primary" type="submit">Aprovar pagamento SANDBOX</button></form>
        <form method="post" action="{{ route('demo.payment', $record->id) }}">@csrf<input type="hidden" name="outcome" value="decline"><input type="hidden" name="idempotency_key" value="{{ \Illuminate\Support\Str::random(32) }}"><button class="sm-btn sm-btn-secondary" type="submit">Simular recusa</button></form>
        <form method="post" action="{{ route('demo.transition', $record->id) }}">@csrf<input type="hidden" name="action" value="cancel"><button class="sm-btn sm-btn-secondary" type="submit">Cancelar e liberar reserva</button></form>
    </div>
</section>
@endif

@if(in_array($record->status, ['paid_demo','preparing_demo','shipped_demo'], true))
<section class="sm-account-panel mt-6">
    <span class="sm-eyebrow">Pós-venda sandbox</span><h2>Operação do pedido</h2>
    <div class="sm-preview-actions mt-4">
        @foreach(['prepare'=>'Marcar em separação','ship'=>'Marcar como enviado','deliver'=>'Marcar como entregue'] as $action => $label)
        @php($allowed = match($action) {'prepare' => $record->status === 'paid_demo', 'ship' => $record->status === 'preparing_demo', 'deliver' => $record->status === 'shipped_demo', default => false})
        @if($allowed)<form method="post" action="{{ route('demo.transition', $record->id) }}">@csrf<input type="hidden" name="action" value="{{ $action }}"><button class="sm-btn sm-btn-secondary" type="submit">{{ $label }}</button></form>@endif
        @endforeach
    </div>
</section>
@endif

<section class="sm-account-panel mt-6">
    <h2>Tentativas de pagamento</h2>
    @forelse($payments as $payment)<p>{{ $payment->created_at }} · {{ $payment->provider }} · {{ $payment->status }} · {{ $payment->provider_reference }}</p>@empty<p>Nenhuma tentativa ainda.</p>@endforelse
</section>

<section class="sm-account-panel mt-6">
    <h2>Histórico auditável</h2>
    @foreach($events as $event)<p>{{ $event->created_at }} — {{ $event->event_type }}</p>@endforeach
    <a class="sm-btn sm-btn-secondary mt-4" href="{{ route('demo.orders') }}">← Pedidos SANDBOX</a>
</section>
@endsection
