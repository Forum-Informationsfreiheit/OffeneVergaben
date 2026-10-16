<?php

namespace Tests\Unit;

use Illuminate\Foundation\Mix;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UrlHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // fixed root so the expected urls don't depend on APP_URL from .env / compose
        URL::forceRootUrl('http://example.test');
        URL::forceScheme('http');
    }

    public function testLinkToStylesheetReturnsEmptyStringWithoutName()
    {
        $this->assertSame('', link_to_stylesheet(''));
        $this->assertSame('', link_to_stylesheet(null));
    }

    public function testLinkToStylesheet()
    {
        $this->assertSame('http://example.test/css/app.css', link_to_stylesheet('app'));
        $this->assertSame('http://example.test/css/vendor/fontawesome/all.min.css', link_to_stylesheet('vendor/fontawesome/all.min'));
    }

    public function testLinkToStylesheetVersionedUsesMix()
    {
        // replace the real Mix (reads public/mix-manifest.json) with a fake
        $this->instance(Mix::class, function ($path) {
            $this->assertSame('css/app.css', $path);

            return '/css/app.css?id=abc123';
        });

        $this->assertSame('http://example.test/css/app.css?id=abc123', link_to_stylesheet('app', true));
    }

    public function testLinkToScriptReturnsEmptyStringWithoutName()
    {
        $this->assertSame('', link_to_script(''));
        $this->assertSame('', link_to_script(null));
    }

    public function testLinkToScript()
    {
        $this->assertSame('http://example.test/js/app.js', link_to_script('app'));
        $this->assertSame('http://example.test/js/vendor/fontawesome/all.min.js', link_to_script('vendor/fontawesome/all.min'));
    }

    public function testLinkToScriptVersionedUsesMix()
    {
        // replace the real Mix (reads public/mix-manifest.json) with a fake
        $this->instance(Mix::class, function ($path) {
            $this->assertSame('js/app.js', $path);

            return '/js/app.js?id=abc123';
        });

        $this->assertSame('http://example.test/js/app.js?id=abc123', link_to_script('app', true));
    }

    public function testLinkToImageReturnsEmptyStringWithoutName()
    {
        $this->assertSame('', link_to_image(''));
        $this->assertSame('', link_to_image(null));
    }

    public function testLinkToImage()
    {
        $this->assertSame('http://example.test/img/app.png', link_to_image('app.png'));
        $this->assertSame('http://example.test/img/vendor/all.png', link_to_image('vendor/all.png'));
    }
}
