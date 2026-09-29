<?php
namespace App\Support;
use App\Models\SellerOffer;
/** Illustrative photos only for the local, non-commercial DEMO seeder. */
final class DemoMedia {
    private const PHOTOS = [
        'demo-tech-fone'=>'photo-1505740420928-5e560c06d30e',
        'demo-tech-mouse'=>'photo-1527814050087-3793815479db',
        'demo-tech-camera'=>'photo-1516035069371-29a1b244cc32',
        'demo-belle-serum'=>'photo-1556228578-0d85b1a4d571',
        'demo-belle-maquiagem'=>'photo-1596462502278-27bfdc403348',
        'demo-belle-perfume'=>'photo-1541643600914-78b084683601',
        'demo-home-cafe'=>'photo-1495474472287-4d71bcdd2085',
        'demo-home-light'=>'photo-1507473885765-e6ed057f782c',
        'demo-home-bag'=>'photo-1547949003-9792a18a2601',
    ];
    private const BANNERS = [
        'hero'=>'photo-1483985988355-763728e1935b',
        'beauty'=>'photo-1596462502278-27bfdc403348',
        'technology'=>'photo-1505740420928-5e560c06d30e',
        'home'=>'photo-1493663284031-b7e3aefcae8c',
    ];
    private const CATEGORIES = [
        'beleza-e-cuidados'=>'photo-1596462502278-27bfdc403348',
        'moda-e-acessorios'=>'photo-1547949003-9792a18a2601',
        'tecnologia-e-informatica'=>'photo-1505740420928-5e560c06d30e',
        'casa-e-decoracao'=>'photo-1493663284031-b7e3aefcae8c',
        'infantil-e-brinquedos'=>'photo-1558060370-d644479cb6f7',
        'eletrodomesticos'=>'photo-1495474472287-4d71bcdd2085',
    ];
    public static function enabled(): bool {
        return app()->environment('local', 'testing') && ! config('marketplace.checkout_enabled', false);
    }
    public static function product(SellerOffer $offer): ?string {
        if (! self::enabled() || ! str_starts_with($offer->product->slug, 'demo-')) return null;
        return isset(self::PHOTOS[$offer->product->slug]) ? self::url(self::PHOTOS[$offer->product->slug], 640) : null;
    }
    public static function productSlug(string $slug): ?string {
        if (! self::enabled() || ! str_starts_with($slug, 'demo-') || ! isset(self::PHOTOS[$slug])) return null;
        return self::url(self::PHOTOS[$slug], 320);
    }
    public static function category(string $slug): ?string {
        return self::enabled() && isset(self::CATEGORIES[$slug]) ? self::url(self::CATEGORIES[$slug], 240) : null;
    }
    public static function banner(string $key): ?string {
        return self::enabled() && isset(self::BANNERS[$key]) ? self::url(self::BANNERS[$key], $key === 'hero' ? 1100 : 700) : null;
    }
    private static function url(string $photoId, int $width): string {
        return 'https://images.unsplash.com/'.$photoId.'?auto=format&fit=crop&w='.$width.'&q=80';
    }
}
