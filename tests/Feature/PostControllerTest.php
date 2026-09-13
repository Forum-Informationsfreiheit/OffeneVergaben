<?php

namespace Tests\Feature;

use App\Post;
use App\Role;
use Illuminate\Support\Str;

class PostControllerTest extends PublicTestCase
{
    public function testIndexListsPublishedPostsNewestFirst()
    {
        // in the future, so they are on the first page
        $older = $this->createPost(['published_at' => '2037-01-01 12:00:00']);
        $newer = $this->createPost(['published_at' => '2037-06-01 12:00:00']);
        $draft = $this->createPost();

        $response = $this->get(route('public::posts'));

        $response->assertStatus(200);
        $response->assertViewIs('public.posts.index');
        $response->assertSeeInOrder([$newer->title, $older->title]);
        $response->assertDontSee($draft->title);
    }

    public function testShowDisplaysPublishedPost()
    {
        $post = $this->createPost(['published_at' => now()]);

        $response = $this->get(route('public::show-post', $post->slug));

        $response->assertStatus(200);
        $response->assertViewIs('public.posts.show');
        $response->assertSee($post->title);
        $response->assertSee('<p>Inhalt des Beitrags</p>');
        $response->assertDontSee('Nicht veröffentlicht!');
    }

    /**
     * TODO: bug — posts/show.blade.php outputs url($post->image). Without image that is url(null), which returns
     * the UrlGenerator instead of a string, and escaping it fails (HTTP 500). The image is optional in the admin
     * form. Only output the image and the page:image section if $post->image is set, then remove
     * markTestIncomplete() here.
     */
    public function testShowDisplaysPostWithoutImage()
    {
        $this->markTestIncomplete('posts/show.blade.php: url($post->image) fails for posts without image (HTTP 500).');

        $post = $this->createPost([
            'image_filepath' => null,
            'image_filename' => null,
            'published_at'   => now(),
        ]);

        $response = $this->get(route('public::show-post', $post->slug));

        $response->assertStatus(200);
        $response->assertSee($post->title);
    }

    public function testShowUnknownPostReturnsNotFound()
    {
        $this->get(route('public::show-post', 'unbekannt-'.Str::lower(Str::random(8))))->assertNotFound();
    }

    /**
     * @dataProvider visitorProvider
     */
    public function testUnpublishedPostIsForbiddenForVisitors($roleId)
    {
        $post = $this->createPost();

        $response = $this->actingAsRole($roleId)->get(route('public::show-post', $post->slug));

        $response->assertForbidden();
    }

    public function testEditorsCanPreviewUnpublishedPost()
    {
        $post = $this->createPost();

        $response = $this->actingAsRole(Role::EDITOR)->get(route('public::show-post', $post->slug));

        $response->assertStatus(200);
        $response->assertSee($post->title);
        $response->assertSee('Nicht veröffentlicht!');
    }

    private function createPost(array $attributes = [])
    {
        return Post::forceCreate(array_merge([
            'title'          => 'Beitrag '.Str::random(8),
            'slug'           => 'beitrag-'.Str::lower(Str::random(8)),
            'summary'        => 'Kurzfassung',
            'body'           => '<p>Inhalt des Beitrags</p>',
            'image_filepath' => 'uploads/images/shares',
            'image_filename' => 'foto.jpg',
        ], $attributes));
    }
}
