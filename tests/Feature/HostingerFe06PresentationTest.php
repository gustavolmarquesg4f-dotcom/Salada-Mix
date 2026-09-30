<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Support\DemoMedia;
use Database\Seeders\CatalogCategorySeeder;
use Database\Seeders\MarketplaceDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class HostingerFe06PresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_home_is_the_dynamic_laravel_experience_v3(): void
    {
        $originalEnv = app()->environment();
        $originalUrl = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);
            $this->seed(MarketplaceDemoSeeder::class);

            $response = $this->get('/')->assertOk()
                ->assertSee('Seu mix de estilos')
                ->assertSee('Nossos departamentos')
                ->assertSee('Quero vender')
                ->assertSee('sm-home-v3', false)
                ->assertSee('sm-home-v3-side', false)
                ->assertSee('(DEMO)');

            $this->assertNotInstanceOf(BinaryFileResponse::class, $response->baseResponse);
        } finally {
            config(['app.url' => $originalUrl]);
            app()->detectEnvironment(fn (): string => $originalEnv);
        }
    }

    public function test_every_seeded_department_has_an_hml_visual_reference(): void
    {
        $originalEnv = app()->environment();
        $originalUrl = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);
            $this->seed(CatalogCategorySeeder::class);

            Category::query()->where('is_active', true)->each(function (Category $category): void {
                $this->assertNotNull(DemoMedia::category($category->slug), $category->slug);
            });
        } finally {
            config(['app.url' => $originalUrl]);
            app()->detectEnvironment(fn (): string => $originalEnv);
        }
    }

    public function test_published_fe06_preview_remains_available_as_historical_reference(): void
    {
        $source = file_get_contents(base_path('preview/index.html'));
        $published = file_get_contents(public_path('fe06/index.html'));

        $this->assertNotFalse($source);
        $this->assertNotFalse($published);
        $this->assertSame(
            $published,
            str_replace(
                '<script src="assets/app.js" defer></script>',
                '<script src="assets/app.js" defer></script>'."\n".'    <script src="/fe06/assets/hml-bridge.js" defer></script>',
                str_replace('<meta charset="utf-8">', '<meta charset="utf-8">'."\n".'    <base href="/fe06/">', $source)
            )
        );
        $this->assertStringContainsString(
            'location.assign(path)',
            file_get_contents(public_path('fe06/assets/hml-bridge.js'))
        );
    }
}
