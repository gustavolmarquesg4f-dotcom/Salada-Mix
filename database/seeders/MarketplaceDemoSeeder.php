<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\SellerOffer;
use App\Models\StockLevel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MarketplaceDemoSeeder extends Seeder
{
    /**
     * Demo catalogue for FE-02. Never enabled in staging/production.
     * Fake CNPJ values and .test emails are intentionally not valid commercial credentials.
     */
    public function run(): void
    {
        $staging = app()->environment('staging')
            && parse_url((string) config('app.url'), PHP_URL_HOST) === 'ivory-rook-276202.hostingersite.com';
        if ((! app()->environment('local', 'testing') && ! $staging)
            || config('marketplace.checkout_enabled')
            || config('marketplace.order_drafts_enabled')
            || config('marketplace.payments_provider') !== 'none') {
            throw new RuntimeException('Seeder DEMO exige ambiente local/teste ou HML específica e recursos comerciais desligados.');
        }

        // A homologação demonstrativa nunca deve receber registros comerciais reais.
        if ($staging && (
            DB::table('sellers')->whereNotIn('cnpj', ['00000000000000', '00000000000001', '00000000000002'])->exists()
            || DB::table('products')->where('slug', 'not like', 'demo-%')->exists()
            || DB::table('orders')->exists()
        )) {
            throw new RuntimeException('HML contém dados não demonstrativos; seeding bloqueado sem modificar registros.');
        }

        $this->call(CatalogCategorySeeder::class);

        $shops = [
            [
                'email' => 'mixtech@saladamix-demo.test',
                'cnpj' => '00000000000000',
                'name' => 'MixTech (DEMO)',
                'products' => [
                    ['tech-fone', 'tecnologia-e-informatica', 'Fone Bluetooth sem fio (DEMO)', 12990, 16, 300, 20, 15, 8],
                    ['tech-mouse', 'tecnologia-e-informatica', 'Mouse ergonômico sem fio (DEMO)', 6990, 14, 180, 14, 9, 5],
                    ['tech-camera', 'tecnologia-e-informatica', 'Câmera compacta (DEMO)', 34900, 8, 450, 14, 10, 8],
                ],
            ],
            [
                'email' => 'belle@saladamix-demo.test',
                'cnpj' => '00000000000001',
                'name' => 'BelleStore (DEMO)',
                'products' => [
                    ['belle-serum', 'beleza-e-cuidados', 'Sérum facial vitamina C (DEMO)', 4990, 18, 120, 12, 5, 5],
                    ['belle-maquiagem', 'beleza-e-cuidados', 'Kit de maquiagem (DEMO)', 7990, 10, 350, 22, 16, 6],
                    ['belle-perfume', 'beleza-e-cuidados', 'Perfume floral 50 ml (DEMO)', 13990, 12, 250, 14, 8, 5],
                ],
            ],
            [
                'email' => 'mixcasa@saladamix-demo.test',
                'cnpj' => '00000000000002',
                'name' => 'MixCasa (DEMO)',
                'products' => [
                    ['home-cafe', 'casa-e-decoracao', 'Cafeteira de vidro 600 ml (DEMO)', 7850, 20, 650, 18, 14, 18],
                    ['home-light', 'casa-e-decoracao', 'Luminária de mesa (DEMO)', 10490, 15, 900, 30, 20, 15],
                    ['home-bag', 'moda-e-acessorios', 'Bolsa transversal ajustável (DEMO)', 8990, 9, 400, 28, 22, 8],
                ],
            ],
        ];

        DB::transaction(function () use ($shops, $staging): void {
            foreach ($shops as $shop) {
                $owner = User::query()->firstOrCreate(
                    ['email' => $shop['email']],
                    ['name' => $shop['name'].' — conta técnica', 'password' => Str::random(64)]
                );

                $seller = Seller::query()->firstOrCreate(
                    ['cnpj' => $shop['cnpj']],
                    [
                        'owner_user_id' => $owner->id,
                        'legal_name' => $shop['name'].' — NÃO COMERCIAL',
                        'trade_name' => $shop['name'],
                        'contact_email' => $shop['email'],
                        'status' => 'active',
                        'approved_at' => now(),
                    ]
                );

                if ($seller->contact_email !== $shop['email']) {
                    throw new RuntimeException('Conflito de identificação fictícia; não alterar empresa existente.');
                }

                SellerMembership::query()->firstOrCreate(
                    ['seller_id' => $seller->id, 'user_id' => $owner->id],
                    ['role' => 'owner', 'status' => 'active']
                );

                if ($staging && ! DB::table('shipping_origins')->where('seller_id', $seller->id)->exists()) {
                    DB::table('shipping_origins')->insert([
                        'id' => (string) Str::ulid(), 'seller_id' => $seller->id,
                        'label' => 'Origem fictícia (DEMO)', 'postal_code' => '70000000',
                        'street' => 'Rua de Demonstração', 'number' => '100',
                        'neighborhood' => 'Bairro Fictício', 'city' => 'Cidade de Teste',
                        'state' => 'DF', 'is_default' => true, 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }

                foreach ($shop['products'] as [$key, $categorySlug, $name, $price, $quantity, $weight, $length, $width, $height]) {
                    $category = Category::query()->where('slug', $categorySlug)->firstOrFail();
                    $product = Product::query()->firstOrCreate(
                        ['slug' => 'demo-'.$key],
                        [
                            'category_id' => $category->id,
                            'created_by_seller_id' => $seller->id,
                            'name' => $name,
                            'description' => 'Produto fictício para validação visual do Salada Mix. Não disponível para compra.',
                            'weight_grams' => $weight, 'length_cm' => $length,
                            'width_cm' => $width, 'height_cm' => $height,
                            'review_status' => 'approved',
                            'reviewed_at' => now(),
                        ]
                    );

                    if ($product->created_by_seller_id !== $seller->id) {
                        throw new RuntimeException('Conflito de propriedade de produto fictício.');
                    }
                    if (! $product->weight_grams || ! $product->length_cm || ! $product->width_cm || ! $product->height_cm) {
                        $product->update([
                            'weight_grams' => $weight, 'length_cm' => $length,
                            'width_cm' => $width, 'height_cm' => $height,
                        ]);
                    }

                    $offer = SellerOffer::query()->firstOrCreate(
                        ['seller_id' => $seller->id, 'sku' => 'DEMO-'.Str::upper($key)],
                        [
                            'product_id' => $product->id,
                            'price_cents' => $price,
                            'currency' => 'BRL',
                            'review_status' => 'approved',
                            'reviewed_at' => now(),
                        ]
                    );

                    StockLevel::query()->firstOrCreate(
                        ['offer_id' => $offer->id],
                        [
                            'seller_id' => $seller->id,
                            'quantity_on_hand' => $quantity,
                            'quantity_reserved' => 0,
                        ]
                    );
                }
            }
        });
    }
}

