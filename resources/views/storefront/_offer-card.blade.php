@php($uploadedImage = $offer->product->media->first())
@php($demoImage = $uploadedImage ? null : \App\Support\DemoMedia::product($offer))
<article class="sm-product-card">
    <a href="{{ route('storefront.offer', $offer) }}" class="sm-product-link" aria-label="Ver oferta: {{ $offer->product->name }}">
        <div class="sm-product-visual {{ ($demoImage || $uploadedImage) ? 'sm-product-photo' : '' }}">
            @if($uploadedImage)
                <img class="sm-demo-image" src="{{ route('media.show', $uploadedImage) }}" alt="{{ $uploadedImage->alt }}" loading="lazy" width="480" height="480">
            @elseif($demoImage)
                <img class="sm-demo-image" src="{{ $demoImage }}" alt="Imagem ilustrativa: {{ $offer->product->name }}" loading="lazy" width="480" height="480" referrerpolicy="no-referrer">
                <span class="sm-product-demo-badge">DEMO</span>
            @else
                <span class="sm-product-no-image" aria-label="Imagem do produto ainda não cadastrada">{{ $offer->product->category->name }}</span>
            @endif
        </div>
        <div class="sm-product-body">
            <p class="sm-product-seller">{{ $offer->seller->trade_name }}</p>
            <h3 class="sm-product-name">{{ $offer->product->name }}</h3>
            <p class="sm-product-price">R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</p>
            <p class="sm-product-hint">Ver detalhes <span aria-hidden="true">→</span></p>
        </div>
    </a>
    <x-salada.commerce-actions :offer="$offer" />
</article>
