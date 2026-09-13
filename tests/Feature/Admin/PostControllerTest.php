<?php

namespace Tests\Feature\Admin;

use App\Post;
use App\Tag;
use Illuminate\Support\Str;

class PostControllerTest extends AdminTestCase
{
    public function testIndexListsPosts()
    {
        $post = $this->createPost();

        $response = $this->actingAs($this->createEditor())->get('/admin/posts');

        $response->assertStatus(200);
        $response->assertViewIs('admin.posts.index');
        $response->assertSee($post->title);
    }

    public function testCreateFormLoads()
    {
        $response = $this->actingAs($this->createEditor())->get('/admin/posts/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.posts.create');
    }

    public function testStoreCreatesPostWithTagsAndImage()
    {
        $editor = $this->createEditor();
        $tag    = $this->createTag();
        $title  = 'Beitrag '.Str::random(8);

        $response = $this->actingAs($editor)->post('/admin/posts/store', [
            'title'          => $title,
            'summary'        => 'Kurzfassung',
            'body'           => 'Inhalt',
            'tags'           => [$tag->id],
            // the file manager passes the full URL of the selected image
            'image_filepath' => url('uploads/images/shares/foto.jpg'),
        ]);

        $response->assertRedirect(route('admin::posts'));

        $post = Post::where('title', $title)->first();
        $this->assertNotNull($post);
        $this->assertSame(Str::slug($title), $post->slug);
        $this->assertEquals($editor->id, $post->author_id);
        $this->assertSame('uploads/images/shares', $post->image_filepath);
        $this->assertSame('foto.jpg', $post->image_filename);
        $this->assertNull($post->published_at);
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $tag->id]);
    }

    public function testStoreRequiresTitleWithAtLeastThreeCharacters()
    {
        $response = $this->actingAs($this->createEditor())->post('/admin/posts/store', ['title' => 'ab']);

        $response->assertSessionHasErrors('title');
    }

    public function testEditFormLoads()
    {
        $post = $this->createPost();

        $response = $this->actingAs($this->createEditor())->get('/admin/posts/edit/'.$post->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.posts.edit');
    }

    public function testEditUnknownPostReturnsNotFound()
    {
        $this->actingAs($this->createEditor())->get('/admin/posts/edit/0')->assertNotFound();
    }

    public function testUpdateChangesPostAndReplacesTags()
    {
        $post   = $this->createPost();
        $oldTag = $this->createTag();
        $newTag = $this->createTag();
        $post->tags()->attach($oldTag);
        $title  = 'Geändert '.Str::random(8);

        $response = $this->actingAs($this->createEditor())->patch('/admin/posts/update', [
            'id'      => $post->id,
            'title'   => $title,
            'summary' => '',
            'body'    => 'Neuer Inhalt',
            'slug'    => $post->slug,
            'tags'    => [$newTag->id],
        ]);

        $response->assertRedirect(route('admin::posts'));
        $this->assertDatabaseHas('posts', [
            'id'      => $post->id,
            'title'   => $title,
            'summary' => '',
            'body'    => 'Neuer Inhalt',
            'slug'    => $post->slug,
        ]);
        $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id, 'tag_id' => $oldTag->id]);
        $this->assertDatabaseHas('post_tag', ['post_id' => $post->id, 'tag_id' => $newTag->id]);
    }

    public function testPublishAndUnpublish()
    {
        $post   = $this->createPost();
        $editor = $this->createEditor();

        $response = $this->actingAs($editor)->patch('/admin/posts/publish', ['id' => $post->id, 'mode' => 'publish']);
        $response->assertRedirect(route('admin::posts'));
        $this->assertNotNull($post->fresh()->published_at);

        $this->actingAs($editor)->patch('/admin/posts/publish', ['id' => $post->id, 'mode' => 'unpublish']);
        $this->assertNull($post->fresh()->published_at);
    }

    public function testDestroyDeletesPostAndItsTagLinks()
    {
        $post = $this->createPost();
        $post->tags()->attach($this->createTag());

        $response = $this->actingAs($this->createEditor())->delete('/admin/posts/destroy', ['id' => $post->id]);

        $response->assertRedirect(route('admin::posts'));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('post_tag', ['post_id' => $post->id]);
    }

    public function testUpdateRegeneratesClearedSlug()
    {
        $post  = $this->createPost();
        $title = 'Neuer Titel '.Str::random(8);

        $this->actingAs($this->createEditor())->patch('/admin/posts/update', [
            'id'    => $post->id,
            'title' => $title,
            'body'  => 'Inhalt',
            'slug'  => '',
        ]);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => Str::slug($title)]);
    }

    /**
     * A slug changed by hand is made unique. The image comes as full url from the file manager.
     */
    public function testUpdateMakesChangedSlugUniqueAndStoresImage()
    {
        $post  = $this->createPost();
        $taken = $this->createPost();

        $this->actingAs($this->createEditor())->patch('/admin/posts/update', [
            'id'             => $post->id,
            'title'          => $post->title,
            'body'           => 'Inhalt',
            'slug'           => $taken->slug,
            'image_filepath' => url('uploads/images/shares/neu.jpg'),
        ]);

        $post->refresh();
        $this->assertStringStartsWith($taken->slug.'-', $post->slug);
        $this->assertSame('uploads/images/shares', $post->image_filepath);
        $this->assertSame('neu.jpg', $post->image_filename);
    }

    private function createPost()
    {
        return Post::forceCreate([
            'title'   => 'Beitrag '.Str::random(8),
            'slug'    => 'beitrag-'.Str::lower(Str::random(8)),
            'summary' => 'Kurzfassung',
            'body'    => 'Inhalt',
        ]);
    }

    private function createTag()
    {
        return Tag::create(['name' => 'Tag '.Str::random(8)]);
    }
}
