<?php

namespace Tests\Feature;

use Tests\TestCase;

class HostingerDemoHubTest extends TestCase
{
    public function test_demo_hub_is_hidden_outside_the_specific_safe_staging_environment(): void
    {
        $this->get('/demo')->assertNotFound();
    }

    public function test_demo_hub_displays_persisted_fixture_counts_in_safe_staging(): void
    {
        $originalEnv = app()->environment();
        $originalUrl = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com']);

            $this->seed(\Database\Seeders\MarketplaceDemoSeeder::class);

            $this->get('/demo')->assertOk()
                ->assertSee('Explore o Salada Mix em funcionamento')
                ->assertSee('3</strong>', false)
                ->assertSee('9</strong>', false)
                ->assertSee('Nenhuma compra ou cobrança real');
        } finally {
            config(['app.url' => $originalUrl]);
            app()->detectEnvironment(fn (): string => $originalEnv);
        }
    }

    public function test_demo_hub_is_hidden_if_any_commercial_flag_is_enabled(): void
    {
        $originalEnv = app()->environment();
        $originalUrl = config('app.url');

        try {
            app()->detectEnvironment(fn (): string => 'staging');
            config(['app.url' => 'https://ivory-rook-276202.hostingersite.com',
                'marketplace.checkout_enabled' => true]);

            $this->get('/demo')->assertNotFound();
        } finally {
            config(['marketplace.checkout_enabled' => false, 'app.url' => $originalUrl]);
            app()->detectEnvironment(fn (): string => $originalEnv);
        }
    }
}
