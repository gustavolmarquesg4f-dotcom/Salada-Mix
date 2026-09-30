@extends('layouts.app')
@section('title', 'Cadastrar produto — Salada Mix')
@section('content')
<nav class="text-sm text-slate-500"><a href="{{ route('seller.offers.index', $seller) }}">Produtos</a> / Novo</nav>
<h1 class="mt-4 text-3xl font-black">Cadastrar produto</h1>
<p class="mt-2 text-slate-600">O anúncio passa por moderação antes de ficar elegível para a vitrine. Não há vendas nesta fase.</p>
<form action="{{ route('seller.offers.store', $seller) }}" method="post" class="mt-7 max-w-2xl space-y-5 rounded-2xl border bg-white p-7">
    @csrf
    <label class="block text-sm font-semibold">Departamento
        <select name="category_id" required class="mt-1 w-full rounded-lg border p-3">
            <option value="">Selecione</option>
            @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
        </select>
    </label>
    <label class="block text-sm font-semibold">Nome <input name="name" required maxlength="180" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Descrição <textarea name="description" rows="4" maxlength="5000" class="mt-1 w-full rounded-lg border p-3">{{ old('description') }}</textarea></label>
    <label class="block text-sm font-semibold">SKU <input name="sku" required maxlength="80" pattern="[A-Za-z0-9._-]+" value="{{ old('sku') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Preço em centavos (ex.: 12990 = R$ 129,90) <input name="price_cents" type="number" min="1" step="1" required value="{{ old('price_cents') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <label class="block text-sm font-semibold">Quantidade inicial <input name="stock_quantity" type="number" min="0" step="1" required value="{{ old('stock_quantity', 0) }}" class="mt-1 w-full rounded-lg border p-3"></label>
    <fieldset class="grid grid-cols-2 gap-4 rounded-xl border p-4"><legend class="px-2 font-semibold">Peso e dimensões para frete</legend>
        <label>Peso (g)<input name="weight_grams" type="number" min="1" max="100000" required value="{{ old('weight_grams') }}" class="mt-1 w-full rounded-lg border p-3"></label>
        <label>Comprimento (cm)<input name="length_cm" type="number" min="1" max="300" required value="{{ old('length_cm') }}" class="mt-1 w-full rounded-lg border p-3"></label>
        <label>Largura (cm)<input name="width_cm" type="number" min="1" max="300" required value="{{ old('width_cm') }}" class="mt-1 w-full rounded-lg border p-3"></label>
        <label>Altura (cm)<input name="height_cm" type="number" min="1" max="300" required value="{{ old('height_cm') }}" class="mt-1 w-full rounded-lg border p-3"></label>
    </fieldset>
    <p class="text-xs text-slate-500">Esses dados alimentam a cotação logística. O anúncio continua sujeito à moderação e o checkout comercial permanece desligado.</p>
    <button type="submit" class="rounded-xl bg-teal-700 px-6 py-3 font-bold text-white">Enviar para análise</button>
</form>
@endsection

