<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminMfa;
use App\Models\Seller;
use App\Models\SellerOffer;
use App\Models\User;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['platform_role' => 'admin', 'email_verified_at' => now()])->save();
        $this->withoutMiddleware(EnsureAdminMfa::class);
        $this->actingAs($admin);
        return $admin;
    }

    public function test_admin_creates_category_and_offer_then_approves_and_unpublishes_it(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->admin();
        $this->get('/admin/gerenciar')->assertOk()->assertSee('Produtos, fotos e departamentos');
        $this->post('/admin/departamentos', ['name' => 'Jardim e Plantas', 'position' => 11])
            ->assertRedirect();
        $this->assertDatabaseHas('categories', ['slug' => 'jardim-e-plantas', 'is_active' => 1]);
        $category = \App\Models\Category::query()->where('slug', 'jardim-e-plantas')->firstOrFail();
        $seller = Seller::query()->firstOrFail();

        $this->post('/admin/produtos', [
            'seller_id' => $seller->id, 'category_id' => $category->id,
            'name' => 'Kit de jardinagem', 'description' => 'Produto teste',
            'sku' => 'JARDIM-1', 'price_cents' => 5500, 'stock_quantity' => 7,
        ])->assertRedirect('/admin/gerenciar');
        $offer = SellerOffer::query()->where('sku', 'JARDIM-1')->firstOrFail();
        $this->assertSame('pending', $offer->review_status);
        $this->assertSame(7, $offer->stock->quantity_on_hand);

        $this->post('/admin/catalogo/'.$offer->id.'/aprovar')->assertRedirect();
        $this->get('/buscar?q=jardinagem')->assertOk()->assertSee('Kit de jardinagem');
        $this->post('/admin/produtos/'.$offer->id.'/despublicar')->assertRedirect();
        $this->get('/buscar?q=jardinagem')->assertOk()->assertDontSee('Kit de jardinagem');
    }

    public function test_admin_uploads_real_image_and_public_only_sees_image_of_published_offer(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        Storage::fake('local');
        $this->admin();
        $offer = SellerOffer::query()->with('product')->firstOrFail();
        $this->post('/admin/produtos/'.$offer->id.'/midias', [
            'image' => UploadedFile::fake()->image('foto.png', 360, 360),
            'alt' => 'Fotografia ilustrativa de um produto',
        ])->assertRedirect();
        $this->assertDatabaseCount('product_media', 1);
        $media = \App\Models\ProductMedia::query()->firstOrFail();
        Storage::disk('local')->assertExists($media->path);
        $this->get('/midia/'.$media->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        auth()->logout();
        $this->get('/midia/'.$media->id)->assertOk();
    }

    public function test_unauthorized_user_cannot_access_admin_or_modify_offer_images(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $user = User::factory()->create();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($user);
        $this->get('/admin/gerenciar')->assertForbidden();
        $offer = SellerOffer::query()->firstOrFail();
        $this->post('/vendedor/'.$offer->seller_id.'/ofertas/'.$offer->id.'/midias', [
            'image' => UploadedFile::fake()->image('other.png', 360, 360),
            'alt' => 'Imagem não autorizada',
        ])->assertForbidden();
        $this->assertDatabaseCount('product_media', 0);
    }
}
