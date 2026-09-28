@props(['offer', 'compact' => false])
<div {{ $attributes->class(['sm-card-actions', 'sm-card-actions-compact' => $compact]) }}>
    @if(auth()->check() && auth()->user()->hasVerifiedEmail())
        <button type="button" class="sm-action-add" data-sm-commerce="cart-add" data-url="{{ route('bff.cart.put', ['offer' => $offer->id]) }}" aria-label="Adicionar {{ $offer->product->name }} à sacola">Adicionar à sacola</button>
        <button type="button" class="sm-action-favorite" data-sm-commerce="wishlist-add" data-url="{{ route('bff.wishlist.put', ['offer' => $offer->id]) }}" aria-label="Salvar {{ $offer->product->name }} nos favoritos" title="Salvar nos favoritos">♡ <span class="sr-only">Favoritar</span></button>
    @else
        <a class="sm-action-add" href="{{ route('login') }}" aria-label="Entrar para adicionar {{ $offer->product->name }} à sacola">Entrar para salvar</a>
        <a class="sm-action-favorite" href="{{ route('login') }}" title="Entre para salvar nos favoritos" aria-label="Entrar para favoritar {{ $offer->product->name }}">♡ <span class="sr-only">Favoritar</span></a>
    @endif
</div>
