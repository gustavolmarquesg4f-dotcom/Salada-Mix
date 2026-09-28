<?php

namespace Tests\Feature;

use App\Domain\Catalog\Actions\CreateSellerOffer;
use App\Domain\Catalog\Actions\ReviewSellerOffer;
use App\Domain\Catalog\Queries\PublicCatalog;
use App\Models\Category;
use App\Models\Seller;
use App\Models\SellerMembership;
use App\Models\SellerOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_seller_owner_submits_product_and_initial_stock(): void
    {
        $owner = User::factory()->create();
        $seller = $this->seller($owner);
        $category = $this->category();

        $this->actingAs($owner)->post(route('seller.offers.store', $seller), $this->payload($category))
            ->assertSessionHasNoErrors()->assertRedirect(route('seller.offers.index', $seller));

        $offer = SellerOffer::query()->firstOrFail();
        $this->assertSame($seller->id, $offer->seller_id);
        $this->assertSame('pending', $offer->review_status);
        $this->assertSame('pending', $offer->product->review_status);
        $this->assertSame('SKU-100', $offer->sku);
        $this->assertSame(12990, $offer->price_cents);
        $this->assertSame(5, $offer->stock->quantity_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'offer_id' => $offer->id, 'seller_id' => $seller->id,
            'reason' => 'initial_registration', 'quantity_delta' => 5,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.offer.submitted']);
    }

    public function test_user_from_another_company_cannot_list_or_create_offers(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $sellerA = $this->seller($ownerA, '00000000000001');
        $this->seller($ownerB, '00000000000002');
        $category = $this->category();

        $this->actingAs($ownerB)->get(route('seller.offers.index', $sellerA))->assertForbidden();
        $this->actingAs($ownerB)->post(route('seller.offers.store', $sellerA), $this->payload($category))
            ->assertForbidden();

        $this->assertDatabaseCount('seller_offers', 0);
    }

    public function test_operations_member_cannot_create_or_submit_product(): void
    {
        $owner = User::factory()->create();
        $operator = User::factory()->create();
        $seller = $this->seller($owner);
        $category = $this->category();

        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $operator->id,
            'role' => 'operations', 'status' => 'active',
        ]);

        $this->actingAs($operator)->get(route('seller.offers.create', $seller))->assertForbidden();
        $this->actingAs($operator)->post(route('seller.offers.store', $seller), $this->payload($category))
            ->assertForbidden();
        $this->actingAs($operator)->get(route('seller.offers.index', $seller))->assertOk();
    }

    public function test_submitted_seller_cannot_create_offers(): void
    {
        $owner = User::factory()->create();
        $seller = $this->seller($owner, '00000000000001', 'submitted');
        $category = $this->category();

        $this->actingAs($owner)->post(route('seller.offers.store', $seller), $this->payload($category))
            ->assertSessionHasErrors('seller');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('seller_offers', 0);
    }

    public function test_client_supplied_seller_id_is_ignored(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $sellerA = $this->seller($ownerA, '00000000000001');
        $sellerB = $this->seller($ownerB, '00000000000002');
        $category = $this->category();

        $payload = $this->payload($category);
        $payload['seller_id'] = $sellerB->id;

        $this->actingAs($ownerA)->post(route('seller.offers.store', $sellerA), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('seller_offers', ['seller_id' => $sellerA->id]);
        $this->assertDatabaseMissing('seller_offers', ['seller_id' => $sellerB->id]);
    }

    public function test_admin_review_does_not_make_offer_public_before_seller_activation(): void
    {
        $owner = User::factory()->create();
        $seller = $this->seller($owner);
        $category = $this->category();
        $offer = app(CreateSellerOffer::class)->execute($seller, $owner, $this->payload($category));
        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin', 'mfa_confirmed_at' => now()])->save();

        $this->actingAs($admin)->withSession(['admin_mfa_user_id' => $admin->id])->post(route('admin.catalog.approve', $offer))->assertSessionHasNoErrors();
        $offer->refresh();
        $this->assertSame('approved', $offer->review_status);
        $this->assertSame('approved', $offer->product->review_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog.offer.approved']);

        $this->assertSame(0, app(PublicCatalog::class)->visibleOffers()->count());
        $this->get(route('storefront.offer', $offer))->assertNotFound();

        // Ativação apenas em fixture; a aplicação NÃO possui rota pública para ativar seller.
        $seller->forceFill(['status' => 'active'])->save();

        $this->get(route('storefront.offer', $offer))->assertOk()
            ->assertSee('Fone de ouvido demonstrativo')
            ->assertSee('checkout está desativado');
        $this->get(route('home'))->assertOk()->assertSee('Fone de ouvido demonstrativo');
        $this->get(route('storefront.category', $category))->assertOk()->assertSee('Fone de ouvido demonstrativo');
    }

    public function test_unapproved_offer_and_out_of_stock_offer_stay_hidden(): void
    {
        $owner = User::factory()->create();
        $seller = $this->seller($owner, '00000000000001', 'active');
        $category = $this->category();
        $offer = app(CreateSellerOffer::class)->execute($seller, $owner, $this->payload($category));

        $this->assertSame(0, app(PublicCatalog::class)->visibleOffers()->count());

        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin', 'mfa_confirmed_at' => now()])->save();
        app(ReviewSellerOffer::class)->execute($offer, $admin, 'approved');

        $offer->stock->update(['quantity_on_hand' => 0]);
        $this->assertSame(0, app(PublicCatalog::class)->visibleOffers()->count());
        $this->get(route('storefront.offer', $offer))->assertNotFound();
    }

    public function test_non_admin_cannot_moderate_catalog_and_repeated_review_is_refused(): void
    {
        $owner = User::factory()->create();
        $seller = $this->seller($owner);
        $category = $this->category();
        $offer = app(CreateSellerOffer::class)->execute($seller, $owner, $this->payload($category));

        $this->actingAs($owner)->post(route('admin.catalog.approve', $offer))->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin', 'mfa_confirmed_at' => now()])->save();
        $this->actingAs($admin)->withSession(['admin_mfa_user_id' => $admin->id])->post(route('admin.catalog.approve', $offer))->assertSessionHasNoErrors();
        $this->actingAs($admin)->withSession(['admin_mfa_user_id' => $admin->id])->post(route('admin.catalog.approve', $offer))->assertSessionHasErrors('offer');
    }

    public function test_seeder_creates_categories_without_default_user_and_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\CatalogCategorySeeder::class);
        $this->seed(\Database\Seeders\CatalogCategorySeeder::class);

        $this->assertDatabaseCount('categories', 10);
        $this->assertDatabaseCount('users', 0);
        $this->get(route('home'))->assertOk()->assertSee('Tecnologia e Informática');
    }

    private function seller(User $owner, string $cnpj = '00000000000001', string $status = 'approved'): Seller
    {
        $seller = Seller::query()->create([
            'owner_user_id' => $owner->id,
            'legal_name' => 'Empresa de Teste',
            'trade_name' => 'Loja de Teste',
            'cnpj' => $cnpj,
            'contact_email' => 'empresa@example.test',
            'status' => $status,
        ]);

        SellerMembership::query()->create([
            'seller_id' => $seller->id, 'user_id' => $owner->id,
            'role' => 'owner', 'status' => 'active',
        ]);

        return $seller;
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => 'Tecnologia', 'slug' => 'tecnologia',
            'position' => 1, 'is_active' => true,
        ]);
    }

    private function payload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'name' => 'Fone de ouvido demonstrativo',
            'description' => 'Produto fictício utilizado apenas na homologação.',
            'sku' => 'SKU-100',
            'price_cents' => 12990,
            'stock_quantity' => 5,
        ];
    }
}

