<?php

namespace Tests\Feature;

use Tests\TestCase;

class HostingerFe06PresentationTest extends TestCase
{
    public function test_staging_home_serves_fe06_visual_preview(): void
    {
        $original = app()->environment();

        try {
            app()->detectEnvironment(fn (): string => 'staging');

            $response = $this->get('/')->assertOk();
            // BinaryFileResponse streams bytes; TestResponse's buffered body is empty.
            $this->assertInstanceOf(
                \Symfony\Component\HttpFoundation\BinaryFileResponse::class,
                $response->baseResponse
            );
            $this->assertSame(
                realpath(public_path('fe06/index.html')),
                realpath($response->baseResponse->getFile()->getPathname())
            );
        } finally {
            app()->detectEnvironment(fn (): string => $original);
        }
    }

    public function test_published_preview_matches_approved_source_except_hostinger_base_path(): void
    {
        $source = file_get_contents(base_path('preview/index.html'));
        $published = file_get_contents(public_path('fe06/index.html'));

        $this->assertNotFalse($source);
        $this->assertNotFalse($published);
        $this->assertSame(
            str_replace('<meta charset="utf-8">', '<meta charset="utf-8">'."\n".'    <base href="/fe06/">', $source),
            str_replace('<script src="assets/app.js" defer></script>', '<script src="assets/app.js" defer></script>' . "\n" . '    <script src="/fe06/assets/hml-bridge.js" defer></script>', $published)
        );
        $this->assertStringContainsString('location.assign(path)', file_get_contents(public_path('fe06/assets/hml-bridge.js')));

        foreach (['app.js', 'preview.css', 'salada-foundation.css', 'salada-catalog.css',
            'salada-visual-demo.css', 'salada-account.css', 'salada-commerce.css',
            'salada-checkout.css', 'salada/salada-mix-logo.svg', 'salada/icons.svg'] as $file) {
            $this->assertSame(
                file_get_contents(base_path('preview/assets/'.$file)),
                file_get_contents(public_path('fe06/assets/'.$file)),
                $file
            );
        }
    }
}
