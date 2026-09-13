<?php

namespace Tests\Feature;

use App\Page;
use App\Role;
use Illuminate\Support\Str;

class PageControllerTest extends PublicTestCase
{
    public function testSearchFindsOrganizationsAndDatasets()
    {
        $token        = Str::random(12);
        $organization = $this->createOrganization(['name' => 'Gemeinde '.$token]);
        $id           = $this->createDataset(['title' => 'Straßenbau '.$token]);

        $response = $this->get(route('public::suchen', ['suche' => $token]));

        $response->assertStatus(200);
        $response->assertViewIs('public.searchresults');
        $response->assertViewHas('organizations', function ($organizations) use ($organization) {
            return $organizations->pluck('id')->all() == [$organization->id];
        });
        $response->assertViewHas('datasets', function ($datasets) use ($id) {
            return $datasets->pluck('id')->all() == [$id];
        });
    }

    /**
     * The search input is split on spaces, and an organization name has to contain every part.
     */
    public function testSearchMatchesAllTokens()
    {
        $token = Str::random(12);
        $match = $this->createOrganization(['name' => 'Gemeinde '.$token.' Nord']);
        $this->createOrganization(['name' => 'Gemeinde '.$token.' Süd']);

        $response = $this->get(route('public::suchen', ['suche' => 'Nord '.$token]));

        $response->assertStatus(200);
        $response->assertViewHas('organizations', function ($organizations) use ($match) {
            return $organizations->pluck('id')->all() == [$match->id];
        });
    }

    public function testEmptySearchShowsNoResults()
    {
        $response = $this->get(route('public::suchen', ['suche' => '  ']));

        $response->assertStatus(200);
        $response->assertViewHas('totalItems', 0);
        $response->assertSee('keine Ergebnisse gefunden');
    }

    /**
     * TODO: bug — Dataset::searchTitleAndDescriptionQuery() doesn't filter on is_current_version, so /suchen
     * lists every version of a dataset, while all other lists only show the current one. Restrict the query to
     * current versions (around both the title and the description condition), then remove
     * markTestIncomplete() here.
     */
    public function testSearchListsOnlyCurrentVersions()
    {
        $this->markTestIncomplete('Dataset::searchTitleAndDescriptionQuery() also returns older versions.');

        $token     = Str::random(12);
        $metasetId = $this->createMetaset();
        $this->createDataset([
            'metaset_id'         => $metasetId,
            'version'            => 1,
            'is_current_version' => 0,
            'title'              => 'Straßenbau '.$token,
        ]);
        $current = $this->createDataset(['metaset_id' => $metasetId, 'version' => 2, 'title' => 'Straßenbau '.$token]);

        $response = $this->get(route('public::suchen', ['suche' => $token]));

        $response->assertViewHas('datasets', function ($datasets) use ($current) {
            return $datasets->pluck('id')->all() == [$current];
        });
    }

    public function testPublishedPageIsShown()
    {
        $page = $this->createPage(['published_at' => now()]);

        $response = $this->get(route('public::show-page', $page->slug));

        $response->assertStatus(200);
        $response->assertViewIs('public.page');
        $response->assertSee($page->title);
        $response->assertSee('<p>Inhalt der Seite</p>');
        $response->assertDontSee('Nicht veröffentlicht!');
    }

    /**
     * @dataProvider visitorProvider
     */
    public function testUnpublishedPageIsForbiddenForVisitors($roleId)
    {
        $page = $this->createPage();

        $response = $this->actingAsRole($roleId)->get(route('public::show-page', $page->slug));

        $response->assertForbidden();
    }

    public function testEditorsCanPreviewUnpublishedPage()
    {
        $page = $this->createPage();

        $response = $this->actingAsRole(Role::EDITOR)->get(route('public::show-page', $page->slug));

        $response->assertStatus(200);
        $response->assertSee($page->title);
        $response->assertSee('Nicht veröffentlicht!');
    }

    public function testUnknownPageReturnsNotFound()
    {
        $this->get(route('public::show-page', 'unbekannt-'.Str::lower(Str::random(8))))->assertNotFound();
    }

    /**
     * @dataProvider reservedSlugProvider
     */
    public function testReservedUrlShowsPageWithThatSlug($slug)
    {
        // the dev database may already have this page: change it instead of adding a second one
        $page = Page::where('slug', $slug)->first() ?: new Page();
        $page->forceFill([
            'title'        => 'Seite '.Str::random(8),
            'slug'         => $slug,
            'body'         => 'Inhalt',
            'published_at' => now(),
        ])->save();

        // percent-encoded like a browser sends it (/überuns → /%C3%BCberuns)
        $response = $this->get('/'.rawurlencode($slug));

        $response->assertStatus(200);
        $response->assertViewIs('public.page');
        $response->assertSee($page->title);
    }

    public function testFaqRedirectsToFrequentlyAskedQuestions()
    {
        $this->get('/faq')->assertRedirect('/frequently-asked-questions');
    }

    public function reservedSlugProvider()
    {
        return [
            'impressum'                  => ['impressum'],
            'datenschutz'                => ['datenschutz'],
            'überuns'                    => ['überuns'],
            'frequently-asked-questions' => ['frequently-asked-questions'],
        ];
    }

    private function createPage(array $attributes = [])
    {
        return Page::forceCreate(array_merge([
            'title' => 'Seite '.Str::random(8),
            'slug'  => 'seite-'.Str::lower(Str::random(8)),
            'body'  => '<p>Inhalt der Seite</p>',
        ], $attributes));
    }
}
