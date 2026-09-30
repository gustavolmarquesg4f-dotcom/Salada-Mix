@extends('layouts.app')
@section('title', 'Cadastrar produto — Salada Mix')
@section('content')
<div class="sm-portal-shell">
    <nav class="sm-breadcrumb"><a href="{{ route('seller.dashboard', $seller) }}">Painel</a><span>/</span><a href="{{ route('seller.offers.index', $seller) }}">Produtos</a><span>/</span><span>Novo produto</span></nav>
    <div class="sm-portal-heading"><div><span class="sm-portal-overline">Novo produto</span><h1>Cadastrar produto</h1><p>Preencha os dados comerciais e logísticos. O produto seguirá para moderação antes de aparecer na vitrine.</p></div></div>
    <form action="{{ route('seller.offers.store', $seller) }}" method="post" class="sm-portal-card sm-portal-form sm-product-entry-form">
        @csrf
        <div class="sm-portal-form-grid">
            <label>Departamento
                <select name="category_id" required>
                    <option value="">Selecione</option>
                    @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </label>
            <label>SKU
                <input name="sku" required maxlength="80" pattern="[A-Za-z0-9._-]+" value="{{ old('sku') }}" placeholder="ABC-001">
            </label>
        </div>
        <label>Nome do produto<input name="name" required maxlength="180" value="{{ old('name') }}" placeholder="Ex.: Fone Bluetooth sem fio"></label>
        <label>Descrição<textarea name="description" rows="5" maxlength="5000" placeholder="Características, materiais, uso e informações importantes">{{ old('description') }}</textarea></label>
        <div class="sm-portal-form-grid">
            <label>Preço (R$)<input name="price" inputmode="decimal" required value="{{ old('price') }}" placeholder="129,90"></label>
            <label>Estoque inicial<input name="stock_quantity" type="number" min="0" max="1000000" required value="{{ old('stock_quantity', 0) }}"></label>
        </div>
        <fieldset class="sm-portal-fieldset"><legend>Peso e dimensões para logística</legend><p>Esses dados são usados na cotação de frete.</p>
            <div class="sm-portal-dimensions">
                <label>Peso (g)<input name="weight_grams" type="number" min="1" max="100000" required value="{{ old('weight_grams') }}"></label>
                <label>Comprimento (cm)<input name="length_cm" type="number" min="1" max="300" required value="{{ old('length_cm') }}"></label>
                <label>Largura (cm)<input name="width_cm" type="number" min="1" max="300" required value="{{ old('width_cm') }}"></label>
                <label>Altura (cm)<input name="height_cm" type="number" min="1" max="300" required value="{{ old('height_cm') }}"></label>
            </div>
        </fieldset>
        <div class="sm-form-actions"><a class="sm-btn sm-btn-secondary" href="{{ route('seller.offers.index', $seller) }}">Cancelar</a><button type="submit" class="sm-btn sm-btn-primary">Enviar para análise →</button></div>
    </form>
</div>
@endsection
