<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendResponsiveHomeTest extends TestCase
{
    public function test_home_styles_cover_desktop_tablet_mobile_and_narrow_mobile_breakpoints(): void
    {
        $css = file_get_contents(resource_path('css/salada-experience.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.sm-home-v3{', $css);
        $this->assertStringContainsString('@media(min-width:1500px)', $css);
        $this->assertStringContainsString('@media(max-width:1280px)', $css);
        $this->assertStringContainsString('@media(max-width:1024px)', $css);
        $this->assertStringContainsString('@media(max-width:860px)', $css);
        $this->assertStringContainsString('@media(max-width:760px)', $css);
        $this->assertStringContainsString('@media(max-width:430px)', $css);
        $this->assertStringContainsString('@media(max-width:360px)', $css);
        $this->assertStringContainsString('scroll-snap-type:x mandatory', $css);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $css);
    }
}
