@extends('layouts.app')
@section('title', 'Gestão do catálogo — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('admin.sellers.index') }}">Administração</a><span>/</span><span>Catálogo</span></nav>
<section class="sm-preview-panel">
    <span class="sm-eyebrow">PAINEL ADMINISTRATIVO PROTEGIDO POR MFA</span>
    <h1>Produtos, fotos e departamentos</h1>
    <p>Cadastre, revise e publique pelo painel, sem modificar código. A publicação de anúncios não ativa pagamentos ou vendas comerciais.</p>
    <a class="sm-btn sm-btn-secondary" href="{{ route('admin.catalog.index') }}">Abrir fila de moderação →</a>
</section>
<section class="sm-account-panel mt-6" aria-labelledby="categories-title">
    <h2 id="categories-title">Departamentos</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="mt-4 flex flex-wrap gap-3">
        @csrf
        <label>Nome <input name="name" required maxlength="120" class="block rounded-lg border p-3" placeholder="Ex.: Jardim"></label>
        <label>Ordem <input type="number" name="position" value="10" min="0" max="65535" required class="block rounded-lg border p-3"></label>
        <button class="sm-btn sm-btn-primary" type="submit">Criar departamento</button>
    </form>
    <div class="mt-4">
    @foreach($categories as $category)
        <div class="sm-checkout-seller flex flex-wrap items-center justify-between gap-3">
            <strong>{{ $category->name }} <small>({{ $category->is_active ? 'ativo' : 'oculto' }})</small></strong>
            <form method="post" action="{{ route('admin.categories.toggle', $category) }}">@csrf<button class="sm-btn sm-btn-secondary">{{ $category->is_active ? 'Ocultar' : 'Ativar' }}</button></form>
        </div>
    @endforeach
    </div>
</section>
<section class="sm-account-panel mt-6" aria-labelledby="new-product-title">
    <h2 id="new-product-title">Cadastrar produto para análise</h2>
    <form method="post" action="{{ route('admin.offers.admin.store') }}" class="mt-4 grid gap-4">
        @csrf
        <label>Loja
            <select name="seller_id" required class="mt-1 block w-full rounded-lg border p-3">
                <option value="">Selecione a loja aprovada</option>
                @foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected(old('seller_id') === $seller->id)>{{ $seller->trade_name }}</option>@endforeach
            </select>
        </label>
        <label>Departamento
            <select name="category_id" required class="mt-1 block w-full rounded-lg border p-3">
                <option value="">Selecione um departamento ativo</option>
                @foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
            </select>
        </label>
        <label>Nome do produto <input name="name" required maxlength="180" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        <label>Descrição <textarea name="description" maxlength="5000" rows="3" class="mt-1 block w-full rounded-lg border p-3">{{ old('description') }}</textarea></label>
        <label>SKU <input name="sku" required maxlength="80" pattern="[A-Za-z0-9._-]+" value="{{ old('sku') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        <label>Preço em centavos <input type="number" min="1" name="price_cents" required value="{{ old('price_cents') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        <label>Estoque inicial <input type="number" min="0" max="1000000" name="stock_quantity" required value="{{ old('stock_quantity', 0) }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        <button class="sm-btn sm-btn-primary" type="submit">Salvar rascunho para revisão</button>
    </form>
</section>
<section class="sm-account-panel mt-6" aria-labelledby="manage-offers-title">
    <h2 id="manage-offers-title">Ofertas cadastradas</h2>
    @forelse($offers as $offer)
        <article class="sm-checkout-seller mt-4">
            <h3>{{ $offer->product->name }}</h3>
            <p>{{ $offer->seller->trade_name }} · {{ $offer->product->category->name }} · SKU {{ $offer->sku }}</p>
            <p><strong>R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</strong> · Estoque {{ $offer->stock?->quantity_on_hand ?? 0 }} · Situação {{ $offer->review_status }}</p>
            @foreach($offer->product->media as $image)<img src="{{ route('media.show', $image) }}" width="100" height="100" alt="{{ $image->alt }}" class="inline-block rounded-lg">@endforeach
            <form method="post" action="{{ route('admin.offers.media.store', $offer) }}" enctype="multipart/form-data" class="mt-3 flex flex-wrap items-end gap-3">
                @csrf
                <label>Foto JPEG, PNG ou WebP <input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="block mt-1"></label>
                <label>Descrição acessível <input name="alt" minlength="3" maxlength="160" required class="block rounded-lg border p-2"></label>
                <button type="submit" class="sm-btn sm-btn-secondary">Adicionar imagem</button>
            </form>
            @if($offer->review_status === 'approved')
                <form method="post" action="{{ route('admin.offers.unpublish', $offer) }}" class="mt-3">@csrf<button class="sm-btn sm-btn-secondary">Despublicar e revisar</button></form>
            @elseif($offer->review_status === 'pending')
                <a class="sm-btn sm-btn-secondary mt-3" href="{{ route('admin.catalog.index') }}">Analisar oferta na moderação →</a>
            @endif
        </article>
    @empty <p>Nenhuma oferta cadastrada.</p>
    @endforelse
    {{ $offers->links() }}
</section>
@endsection
