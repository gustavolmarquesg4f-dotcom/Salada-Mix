@extends('layouts.app')
@section('title', 'Origens de envio — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('seller.dashboard', $seller) }}">Painel da empresa</a><span>/</span><span>Origens de envio</span></nav>
    <div class="sm-portal-heading"><div><span class="sm-portal-overline">Logística · {{ $seller->trade_name }}</span><h1>Origens de envio</h1><p>Cadastre os locais privados usados para calcular frete e organizar a expedição da sua loja.</p></div></div>
    <div class="sm-portal-grid">
        <section class="sm-portal-card">
            <h2>Locais cadastrados</h2>
            <div class="sm-portal-list">
            @forelse($origins as $origin)
                <div class="sm-portal-row sm-portal-row-stack">
                    <div><strong>{{ $origin->label }} @if($origin->is_default)<span class="sm-portal-badge">Principal</span>@endif</strong><p>{{ $origin->street }}, {{ $origin->number }} — {{ $origin->neighborhood }}<br>{{ $origin->city }}/{{ $origin->state }} · {{ substr($origin->postal_code, 0, 5) }}-{{ substr($origin->postal_code, 5) }}</p></div>
                    <form method="POST" action="{{ route('seller.origins.destroy', [$seller, $origin->id]) }}">@csrf @method('DELETE')<button type="submit" class="sm-portal-danger">Excluir origem</button></form>
                </div>
            @empty
                <p class="sm-account-empty">Nenhuma origem cadastrada.</p>
            @endforelse
            </div>
        </section>
        <section class="sm-portal-card">
            <h2>Adicionar origem</h2>
            <p>Use o endereço real de postagem/expedição quando o ambiente comercial for habilitado.</p>
            <form method="POST" action="{{ route('seller.origins.store', $seller) }}" class="sm-portal-form">
                @csrf
                @foreach(['label' => 'Identificação (ex.: depósito central)', 'postal_code' => 'CEP',
                    'street' => 'Rua / avenida', 'number' => 'Número',
                    'complement' => 'Complemento (opcional)', 'neighborhood' => 'Bairro',
                    'city' => 'Cidade', 'state' => 'UF (ex.: DF)'] as $field => $label)
                    <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field) }}" @unless($field === 'complement') required @endunless>@error($field)<span class="sm-field-error">{{ $message }}</span>@enderror</label>
                @endforeach
                <label class="sm-form-check"><input type="checkbox" name="is_default" value="1" @checked(old('is_default'))> Definir como origem principal</label>
                <button class="sm-btn sm-btn-primary" type="submit">Salvar origem</button>
            </form>
        </section>
    </div>
</div>
@endsection
