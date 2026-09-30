<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAdminMfa;
use App\Models\Category;
use App\Models\ProductMedia;
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

    public function test_admin_creates_edits_approves_and_unpublishes_physical_product_without_code_changes(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        $this->admin();
        $this->get('/admin/gerenciar')->assertOk()->assertSee('Catálogo do Salada Mix');
        $this->post('/admin/departamentos', ['name' => 'Jardim e Plantas', 'position' => 11])->assertRedirect();
        $category = Category::query()->where('slug', 'jardim-e-plantas')->firstOrFail();
        $seller = Seller::query()->firstOrFail();

        $this->post('/admin/produtos', [
            'seller_id' => $seller->id, 'category_id' => $category->id,
            'name' => 'Kit de jardinagem', 'description' => 'Produto teste',
            'sku' => 'JARDIM-1', 'price' => '55,00', 'stock_quantity' => 7,
            'weight_grams' => 850, 'length_cm' => 32, 'width_cm' => 20, 'height_cm' => 12,
        ])->assertRedirect('/admin/gerenciar');

        $offer = SellerOffer::query()->where('sku', 'JARDIM-1')->firstOrFail();
        $this->assertSame(5500, $offer->price_cents);
        $this->assertSame(850, $offer->product->weight_grams);
        $this->assertSame(7, $offer->stock->quantity_on_hand);

        $this->post('/admin/catalogo/'.$offer->id.'/aprovar')->assertRedirect();
        $this->get('/buscar?q=jardinagem')->assertOk()->assertSee('Kit de jardinagem');

        $this->patch('/admin/produtos/'.$offer->id, [
            'category_id' => $category->id, 'name' => 'Kit de jardinagem premium',
            'description' => 'Produto teste atualizado', 'price' => '59,90',
            'stock_quantity' => 9, 'weight_grams' => 900,
            'length_cm' => 33, 'width_cm' => 21, 'height_cm' => 13,
        ])->assertRedirect();

        $offer->refresh();
        $this->assertSame(5990, $offer->price_cents);
        $this->assertSame('pending', $offer->review_status);
        $this->assertSame(9, $offer->stock->fresh()->quantity_on_hand);
        $this->assertSame(900, $offer->product->fresh()->weight_grams);
        $this->assertDatabaseHas('inventory_movements', [
            'offer_id' => $offer->id, 'quantity_delta' => 2, 'reason' => 'admin_adjustment',
        ]);
        $this->get('/buscar?q=jardinagem')->assertOk()->assertDontSee('Kit de jardinagem premium');

        $this->post('/admin/catalogo/'.$offer->id.'/aprovar')->assertRedirect();
        $this->get('/buscar?q=jardinagem')->assertOk()->assertSee('Kit de jardinagem premium');
        $this->post('/admin/produtos/'.$offer->id.'/despublicar')->assertRedirect();
        $this->get('/buscar?q=jardinagem')->assertOk()->assertDontSee('Kit de jardinagem premium');
    }

    public function test_admin_uploads_reorders_and_deletes_gallery_images(): void
    {
        $this->seed(MarketplaceDemoSeeder::class);
        Storage::fake('local');
        $this->admin();
        $offer = SellerOffer::query()->with('product')->firstOrFail();

        foreach (['frente.png' => 'Foto frontal do produto', 'lado.png' => 'Foto lateral do produto'] as $file => $alt) {
            $this->post('/admin/produtos/'.$offer->id.'/midias', [
                'image' => UploadedFile::fake()->image($file, 360, 360), 'alt' => $alt,
            ])->assertRedirect();
        }

        $media = ProductMedia::query()->where('product_id', $offer->product_id)->orderBy('position')->get();
        $this->assertCount(2, $media);
        $this->post('/admin/produtos/'.$offer->id.'/midias/'.$media[1]->id.'/capa')->assertRedirect();
        $this->assertSame($media[1]->id, ProductMedia::query()->where('product_id', $offer->product_id)->orderBy('position')->first()->id);

        $toDelete = ProductMedia::query()->where('product_id', $offer->product_id)->orderByDesc('position')->first();
        $path = $toDelete->path;
        $this->delete('/admin/produtos/'.$offer->id.'/midias/'.$toDelete->id)->assertRedirect();
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('product_media', 1);
        $remaining = ProductMedia::query()->firstOrFail();
        $this->assertSame(0, $remaining->position);
        $this->get('/midia/'.$remaining->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
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
