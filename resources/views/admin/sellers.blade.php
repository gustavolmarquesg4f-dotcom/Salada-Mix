@extends('layouts.app')
@section('title', 'Empresas — Administração Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('admin.manage') }}">Administração</a><span>/</span><span>Empresas</span></nav>
    <div class="sm-portal-heading"><div><span class="sm-portal-overline">Administração · MFA</span><h1>Análise de empresas</h1><p>Revise cadastros de vendedores. A aprovação da empresa não habilita automaticamente pagamentos nem ofertas.</p></div><a class="sm-btn sm-btn-secondary" href="{{ route('admin.manage') }}">Catálogo →</a></div>
    <div class="sm-portal-list">
    @forelse ($sellers as $seller)
        <article class="sm-portal-card">
            <div class="sm-portal-row sm-portal-row-plain"><div><span class="sm-portal-badge">{{ $seller->status }}</span><h2>{{ $seller->trade_name }}</h2><p>{{ $seller->legal_name }} · CNPJ {{ $seller->cnpj }} · Responsável {{ $seller->owner->name }}</p></div></div>
            @if (in_array($seller->status, ['submitted', 'under_review'], true))
                <div class="sm-admin-review-actions">
                    <form action="{{ route('admin.sellers.approve', $seller) }}" method="post">@csrf<button class="sm-btn sm-btn-primary">Aprovar cadastro</button></form>
                    <form action="{{ route('admin.sellers.reject', $seller) }}" method="post" class="sm-admin-reject-form">@csrf<label class="sr-only" for="reason-{{ $seller->id }}">Motivo</label><input id="reason-{{ $seller->id }}" name="reason" required minlength="10" maxlength="2000" placeholder="Motivo da rejeição"><button class="sm-btn sm-btn-secondary">Rejeitar</button></form>
                </div>
            @endif
        </article>
    @empty <div class="sm-empty"><strong>Nenhuma solicitação recebida.</strong></div>@endforelse
    </div>
    <div class="mt-6">{{ $sellers->links() }}</div>
</div>
@endsection
