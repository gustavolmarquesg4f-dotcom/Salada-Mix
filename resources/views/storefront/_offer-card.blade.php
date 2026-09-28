<article class="sm-product-card">
    <a href="{{ route('storefront.offer', $offer) }}" class="block" aria-label="Ver oferta: {{ $offer->product->name }}">
        <div class="sm-product-visual" aria-hidden="true">{{ $offer->product->category->name }}</div>
        <div class="sm-product-body">
            <p class="sm-product-seller">Vendido por {{ $offer->seller->trade_name }}</p>
            <h3 class="sm-product-name">{{ $offer->product->name }}</h3>
            <p class="sm-product-price">R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</p>
            <p class="sm-product-hint">Consulte os detalhes · compras indisponíveis nesta fase</p>
        </div>
    </a>
</article>
