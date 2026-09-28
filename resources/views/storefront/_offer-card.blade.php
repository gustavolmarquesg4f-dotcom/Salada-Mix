<article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <a href="{{ route('storefront.offer', $offer) }}" class="block">
        <div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-teal-50 to-slate-100 px-6 text-center">
            <span class="text-2xl font-black text-teal-700">{{ $offer->product->category->name }}</span>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ $offer->seller->trade_name }}</p>
            <h3 class="mt-2 line-clamp-2 min-h-12 text-lg font-bold text-slate-900">{{ $offer->product->name }}</h3>
            <p class="mt-3 text-2xl font-extrabold text-slate-900">R$ {{ number_format($offer->price_cents / 100, 2, ',', '.') }}</p>
            <p class="mt-2 text-sm text-slate-500">Consulte a descrição · vendas ainda indisponíveis</p>
        </div>
    </a>
</article>

