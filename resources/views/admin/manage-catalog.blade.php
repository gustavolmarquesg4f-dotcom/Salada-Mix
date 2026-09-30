@extends('layouts.app')
@section('title', 'Gestão do catálogo — Salada Mix')
@section('content')
<nav class="sm-breadcrumb"><a href="{{ route('home') }}">Início</a><span>/</span><a href="{{ route('admin.sellers.index') }}">Administração</a><span>/</span><span>Catálogo</span></nav>

<section class="sm-preview-panel">
    <span class="sm-eyebrow">ADMINISTRAÇÃO · MFA OBRIGATÓRIO</span>
    <h1>Catálogo do Salada Mix</h1>
    <p>Cadastre, edite, fotografe, revise, publique ou retire produtos sem alterar código. Na homologação, somente vendedores e produtos DEMO são aceitos.</p>
    <div class="sm-preview-actions mt-4">
        <a class="sm-btn sm-btn-secondary" href="{{ route('admin.catalog.index') }}">Fila de moderação →</a>
        <a class="sm-btn sm-btn-secondary" href="{{ route('admin.sellers.index') }}">Empresas →</a>
    </div>
</section>

<section class="sm-account-panel mt-6" aria-labelledby="categories-title">
    <div class="flex flex-wrap items-center justify-between gap-3"><div><span class="sm-eyebrow">Estrutura da vitrine</span><h2 id="categories-title">Departamentos</h2></div></div>
    <form method="post" action="{{ route('admin.categories.store') }}" class="mt-4 flex flex-wrap items-end gap-3">
        @csrf
        <label class="font-semibold">Nome <input name="name" required maxlength="120" class="mt-1 block rounded-lg border p-3" placeholder="Ex.: Jardim"></label>
        <label class="font-semibold">Ordem <input type="number" name="position" value="10" min="0" max="65535" required class="mt-1 block rounded-lg border p-3"></label>
        <button class="sm-btn sm-btn-primary" type="submit">Criar departamento</button>
    </form>
    <div class="mt-4 grid gap-2">
    @foreach($categories as $category)
        <div class="sm-checkout-seller flex flex-wrap items-center justify-between gap-3 p-3">
            <strong>{{ $category->name }} <small>({{ $category->is_active ? 'ativo' : 'oculto' }})</small></strong>
            <form method="post" action="{{ route('admin.categories.toggle', $category) }}">@csrf<button class="sm-btn sm-btn-secondary">{{ $category->is_active ? 'Ocultar' : 'Ativar' }}</button></form>
        </div>
    @endforeach
    </div>
</section>

<section class="sm-account-panel mt-6" aria-labelledby="new-product-title">
    <span class="sm-eyebrow">Novo item</span><h2 id="new-product-title">Cadastrar produto para revisão</h2>
    <form method="post" action="{{ route('admin.offers.admin.store') }}" class="mt-4 grid gap-4">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <label class="font-semibold">Loja
                <select name="seller_id" required class="mt-1 block w-full rounded-lg border p-3">
                    <option value="">Selecione uma loja aprovada</option>
                    @foreach($sellers as $seller)<option value="{{ $seller->id }}" @selected(old('seller_id') === $seller->id)>{{ $seller->trade_name }}</option>@endforeach
                </select>
            </label>
            <label class="font-semibold">Departamento
                <select name="category_id" required class="mt-1 block w-full rounded-lg border p-3">
                    <option value="">Selecione</option>
                    @foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach
                </select>
            </label>
        </div>
        <label class="font-semibold">Nome do produto <input name="name" required maxlength="180" value="{{ old('name') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        <label class="font-semibold">Descrição <textarea name="description" maxlength="5000" rows="4" class="mt-1 block w-full rounded-lg border p-3">{{ old('description') }}</textarea></label>
        <div class="grid gap-4 md:grid-cols-3">
            <label class="font-semibold">SKU <input name="sku" required maxlength="80" pattern="[A-Za-z0-9._-]+" value="{{ old('sku') }}" class="mt-1 block w-full rounded-lg border p-3" placeholder="ABC-001"></label>
            <label class="font-semibold">Preço (R$) <input name="price" inputmode="decimal" required value="{{ old('price') }}" class="mt-1 block w-full rounded-lg border p-3" placeholder="129,90"></label>
            <label class="font-semibold">Estoque <input type="number" min="0" max="1000000" name="stock_quantity" required value="{{ old('stock_quantity', 0) }}" class="mt-1 block w-full rounded-lg border p-3"></label>
        </div>
        <fieldset class="rounded-xl border p-4"><legend class="px-2 font-bold">Peso e dimensões para logística</legend>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label>Peso (g)<input name="weight_grams" type="number" min="1" max="100000" required value="{{ old('weight_grams') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
                <label>Comprimento (cm)<input name="length_cm" type="number" min="1" max="300" required value="{{ old('length_cm') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
                <label>Largura (cm)<input name="width_cm" type="number" min="1" max="300" required value="{{ old('width_cm') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
                <label>Altura (cm)<input name="height_cm" type="number" min="1" max="300" required value="{{ old('height_cm') }}" class="mt-1 block w-full rounded-lg border p-3"></label>
            </div>
        </fieldset>
        <button class="sm-btn sm-btn-primary" type="submit">Salvar rascunho para revisão</button>
    </form>
</section>

<section class="sm-account-panel mt-6" aria-labelledby="manage-offers-title">
    <span class="sm-eyebrow">Operação</span><h2 id="manage-offers-title">Produtos cadastrados</h2>
    @forelse($offers as $offer)
        <article class="sm-checkout-seller mt-5 p-4">
            <div class="flex flex-wrap justify-between gap-3">
                <div><h3 class="text-lg font-black">{{ $offer->product->name }}</h3><p>{{ $offer->seller->trade_name }} · SKU {{ $offer->sku }} · situação <strong>{{ $offer->review_status }}</strong></p></div>
                <strong>R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }} · {{ $offer->stock?->quantity_on_hand ?? 0 }} un.</strong>
            </div>

            <details class="mt-4 rounded-xl border p-4">
                <summary class="cursor-pointer font-bold">Editar dados, preço, estoque e dimensões</summary>
                <form method="post" action="{{ route('admin.offers.admin.update', $offer) }}" class="mt-4 grid gap-3">
                    @csrf @method('PATCH')
                    <label>Departamento<select name="category_id" required class="mt-1 block w-full rounded-lg border p-2">@foreach($categories->where('is_active', true) as $category)<option value="{{ $category->id }}" @selected($offer->product->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    <label>Nome<input name="name" required maxlength="180" value="{{ $offer->product->name }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                    <label>Descrição<textarea name="description" maxlength="5000" rows="3" class="mt-1 block w-full rounded-lg border p-2">{{ $offer->product->description }}</textarea></label>
                    <div class="grid gap-3 md:grid-cols-3">
                        <label>Preço (R$)<input name="price" required value="{{ number_format($offer->price_cents / 100, 2, ',', '') }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                        <label>Estoque<input name="stock_quantity" type="number" min="{{ $offer->stock?->quantity_reserved ?? 0 }}" max="1000000" required value="{{ $offer->stock?->quantity_on_hand ?? 0 }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                        <label>Reservado<input disabled value="{{ $offer->stock?->quantity_reserved ?? 0 }}" class="mt-1 block w-full rounded-lg border bg-slate-100 p-2"></label>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label>Peso (g)<input name="weight_grams" type="number" min="1" max="100000" required value="{{ $offer->product->weight_grams }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                        <label>Comp. (cm)<input name="length_cm" type="number" min="1" max="300" required value="{{ $offer->product->length_cm }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                        <label>Larg. (cm)<input name="width_cm" type="number" min="1" max="300" required value="{{ $offer->product->width_cm }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                        <label>Alt. (cm)<input name="height_cm" type="number" min="1" max="300" required value="{{ $offer->product->height_cm }}" class="mt-1 block w-full rounded-lg border p-2"></label>
                    </div>
                    <p class="text-xs text-slate-500">Qualquer alteração devolve o produto para revisão antes de voltar à vitrine.</p>
                    <button class="sm-btn sm-btn-secondary" type="submit">Salvar alterações e revisar</button>
                </form>
            </details>

            <div class="mt-4 flex flex-wrap gap-3">
                @foreach($offer->product->media as $image)
                <div class="rounded-xl border p-2 text-center">
                    <img src="{{ route('media.show', $image) }}" width="110" height="110" alt="{{ $image->alt }}" class="h-28 w-28 rounded-lg object-cover">
                    <small class="mt-1 block">{{ $image->position === 0 ? 'CAPA' : 'Imagem '.($image->position + 1) }}</small>
                    @if($image->position !== 0)<form method="post" action="{{ route('admin.offers.media.cover', [$offer, $image]) }}" class="mt-1">@csrf<button class="text-xs font-bold text-emerald-800">Definir capa</button></form>@endif
                    <form method="post" action="{{ route('admin.offers.media.destroy', [$offer, $image]) }}" class="mt-1">@csrf @method('DELETE')<button class="text-xs font-bold text-red-700">Remover</button></form>
                </div>
                @endforeach
            </div>
            <form method="post" action="{{ route('admin.offers.media.store', $offer) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <label>Foto JPEG, PNG ou WebP<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="mt-1 block"></label>
                <label>Texto alternativo<input name="alt" minlength="3" maxlength="160" required class="mt-1 block rounded-lg border p-2"></label>
                <button type="submit" class="sm-btn sm-btn-secondary">Adicionar foto</button>
            </form>
            @if($offer->review_status === 'approved')
                <form method="post" action="{{ route('admin.offers.unpublish', $offer) }}" class="mt-4">@csrf<button class="sm-btn sm-btn-secondary">Despublicar e revisar</button></form>
            @elseif($offer->review_status === 'pending')
                <a class="sm-btn sm-btn-secondary mt-4" href="{{ route('admin.catalog.index') }}">Analisar na moderação →</a>
            @endif
        </article>
    @empty
        <p class="mt-4">Nenhuma oferta cadastrada.</p>
    @endforelse
    <div class="mt-5">{{ $offers->links() }}</div>
</section>
@endsection
