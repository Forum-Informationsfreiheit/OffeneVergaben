<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\Subscription;
use Illuminate\Support\Str;

/**
 * The pagination markup of the three pages that render Laravel's built-in paginator view:
 * /neuigkeiten, /admin/datasets and /admin/subscriptions. Every other list page passes
 * public.partials.pagination to links() and is therefore independent of the framework default.
 *
 * Which built-in view is used is decided by the Paginator::useBootstrapFour() call in
 * AppServiceProvider. The call matters because the meaning of its sibling changed with the
 * Laravel 8 -> 9 upgrade: useBootstrap() rendered Bootstrap 4 on 8 and renders Bootstrap 5
 * from 9 on. The templates carry Bootstrap 4 markup, and a wrong view is not an error — the
 * page still renders, only the pager is styled by classes that do not exist here. These tests
 * exist so the next framework upgrade fails in CI instead of on the page.
 */
class PaginationTest extends DatabaseTestCase
{
    // Bootstrap 5's paginator wraps everything in a flex nav with a separate mobile block,
    // neither of which the Bootstrap 4 view has. The full opening tags are matched, not single
    // classes: `d-sm-none` and friends are ordinary Bootstrap 4 utilities and appear elsewhere
    // in the admin layout.
    const BOOTSTRAP_5_MARKERS = [
        '<nav class="d-flex justify-items-center justify-content-between">',
        '<div class="d-flex justify-content-between flex-fill d-sm-none">',
    ];

    public function testNewsPagerUsesBootstrapFourMarkup()
    {
        // PostController@index paginates at 10
        foreach (range(1, 11) as $i) {
            $this->createPost();
        }

        $this->assertBootstrapFourPager($this->get('/neuigkeiten'));
    }

    public function testAdminDatasetsPagerUsesBootstrapFourMarkup()
    {
        // Admin\DatasetController@index paginates at 50
        foreach (range(1, 51) as $i) {
            $this->createDataset();
        }

        $response = $this->actingAs($this->createUser(Role::EDITOR))->get('/admin/datasets');

        $this->assertBootstrapFourPager($response);
    }

    public function testAdminSubscriptionsPagerUsesBootstrapFourMarkup()
    {
        // Admin\SubscriptionController@index paginates at 50
        $subscriber = $this->createUser(Role::SUBSCRIBER);
        foreach (range(1, 51) as $i) {
            Subscription::forceCreate([
                'user_id' => $subscriber->id,
                'type'    => Subscription::TYPE_DATASET,
                'title'   => 'Abo '.Str::random(8),
                'query'   => 'types%5B%5D=auftrag',
            ]);
        }

        $response = $this->actingAs($this->createUser(Role::EDITOR))->get('/admin/subscriptions');

        $this->assertBootstrapFourPager($response);
    }

    /**
     * @param \Illuminate\Testing\TestResponse $response
     */
    private function assertBootstrapFourPager($response)
    {
        $response->assertStatus(200);

        // the pager is rendered at all — without this the assertions below pass on any page
        $response->assertSee('<ul class="pagination">', false);
        $response->assertSee('<li class="page-item active" aria-current="page">', false);

        foreach (self::BOOTSTRAP_5_MARKERS as $marker) {
            $response->assertDontSee($marker, false);
        }
    }

    private function createPost()
    {
        return Post::forceCreate([
            'title'        => 'Beitrag '.Str::random(8),
            'slug'         => 'beitrag-'.Str::lower(Str::random(8)),
            'summary'      => 'Kurzfassung',
            'body'         => 'Inhalt',
            'published_at' => now(),
        ]);
    }
}
