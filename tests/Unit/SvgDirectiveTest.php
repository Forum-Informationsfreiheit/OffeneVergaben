<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The @svg directive (AppServiceProvider) inlines an SVG file from public/ with a css class.
 */
class SvgDirectiveTest extends TestCase
{
    public function testInlinesSvgWithClass()
    {
        $compiled = Blade::compileString("@svg('/img/icons/filter.svg','filter')");

        $this->assertStringStartsWith('<svg', $compiled);
        $this->assertStringContainsString('class="filter"', $compiled);
    }
}
