<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontpageTest extends TestCase
{
    /**
     * The frontpage renders with the top offerors/contractors and the latest posts.
     *
     * @return void
     */
    public function testFrontpageLoads()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('public.frontpage');
        $response->assertViewHasAll([
            'topOfferorsByCount',
            'topOfferorsBySum',
            'topContractorsByCount',
            'topContractorsBySum',
            'posts',
        ]);
        $response->assertSee('Nach Lieferanten oder Auftraggebern suchen');
    }
}
