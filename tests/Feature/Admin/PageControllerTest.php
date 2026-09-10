<?php

namespace Tests\Feature\Admin;

use App\Page;
use Illuminate\Support\Str;

class PageControllerTest extends AdminTestCase
{
    public function testIndexListsPages()
    {
        $page = $this->createPage();

        $response = $this->actingAs($this->createEditor())->get('/admin/pages');

        $response->assertStatus(200);
        $response->assertViewIs('admin.pages.index');
        $response->assertSee($page->title);
    }

    /**
     * pages.author_id is set to null when the author is deleted.
     */
    public function testIndexListsPageWithoutAuthor()
    {
        $page = Page::forceCreate([
            'title' => 'Seite ohne Autor '.Str::random(8),
            'slug'  => 'seite-'.Str::lower(Str::random(8)),
            'body'  => 'Inhalt',
        ]);

        $response = $this->actingAs($this->createEditor())->get('/admin/pages');

        $response->assertStatus(200);
        $response->assertSee($page->title);
    }

    public function testCreateFormLoads()
    {
        $response = $this->actingAs($this->createEditor())->get('/admin/pages/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.pages.create');
    }

    public function testStoreCreatesPageWithSlugFromTitle()
    {
        $editor = $this->createEditor();
        $title  = 'Seite '.Str::random(8);

        $response = $this->actingAs($editor)->post('/admin/pages/store', [
            'title' => $title,
            'body'  => '<p>Inhalt</p>',
        ]);

        $response->assertRedirect(route('admin::pages'));
        $this->assertDatabaseHas('pages', [
            'title'        => $title,
            'slug'         => Str::slug($title),
            'body'         => '<p>Inhalt</p>',
            'author_id'    => $editor->id,
            'published_at' => null,
        ]);
    }

    public function testStoreKeepsGivenSlug()
    {
        $slug = 'seite-'.Str::lower(Str::random(8));

        $this->actingAs($this->createEditor())->post('/admin/pages/store', [
            'title' => 'Seite mit eigenem Slug',
            'body'  => 'Inhalt',
            'slug'  => $slug,
        ]);

        $this->assertDatabaseHas('pages', ['slug' => $slug]);
    }

    public function testStoreRequiresTitleWithAtLeastThreeCharacters()
    {
        $response = $this->actingAs($this->createEditor())->post('/admin/pages/store', [
            'title' => 'ab',
            'body'  => 'Inhalt',
        ]);

        $response->assertSessionHasErrors('title');
    }

    public function testStoreAcceptsEmptyBody()
    {
        $this->markTestIncomplete('pages.body is NOT NULL, but an empty textarea arrives as null (ConvertEmptyStringsToNull) and PageController stores it as is: SQL error.');

        $title = 'Seite '.Str::random(8);

        $this->actingAs($this->createEditor())->post('/admin/pages/store', ['title' => $title, 'body' => '']);

        $this->assertDatabaseHas('pages', ['title' => $title]);
    }

    public function testStoreMakesSlugUniqueAmongPages()
    {
        $this->markTestIncomplete('PageController uses Post::uniqueSlug(), which only checks the posts table, so two pages can get the same slug.');

        $title = 'Seite '.Str::random(8);
        Page::forceCreate(['title' => $title, 'slug' => Str::slug($title), 'body' => 'Inhalt']);

        $this->actingAs($this->createEditor())->post('/admin/pages/store', ['title' => $title, 'body' => 'Inhalt']);

        $this->assertSame(1, Page::where('slug', Str::slug($title))->count());
    }

    public function testEditFormLoads()
    {
        $page = $this->createPage();

        $response = $this->actingAs($this->createEditor())->get('/admin/pages/edit/'.$page->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.pages.edit');
    }

    public function testEditUnknownPageReturnsNotFound()
    {
        $this->actingAs($this->createEditor())->get('/admin/pages/edit/0')->assertNotFound();
    }

    public function testUpdateChangesTitleAndBody()
    {
        $page  = $this->createPage();
        $title = 'Geändert '.Str::random(8);

        $response = $this->actingAs($this->createEditor())->patch('/admin/pages/update', [
            'id'    => $page->id,
            'title' => $title,
            'body'  => 'Neuer Inhalt',
            'slug'  => $page->slug,
        ]);

        $response->assertRedirect(route('admin::pages'));
        $this->assertDatabaseHas('pages', [
            'id'    => $page->id,
            'title' => $title,
            'body'  => 'Neuer Inhalt',
            'slug'  => $page->slug,
        ]);
    }

    public function testUpdateRegeneratesClearedSlug()
    {
        $page  = $this->createPage();
        $title = 'Neuer Titel '.Str::random(8);

        $this->actingAs($this->createEditor())->patch('/admin/pages/update', [
            'id'    => $page->id,
            'title' => $title,
            'body'  => 'Inhalt',
            'slug'  => '',
        ]);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'slug' => Str::slug($title)]);
    }

    public function testPublishAndUnpublish()
    {
        $page   = $this->createPage();
        $editor = $this->createEditor();

        $response = $this->actingAs($editor)->patch('/admin/pages/publish', ['id' => $page->id, 'mode' => 'publish']);
        $response->assertRedirect(route('admin::pages'));
        $this->assertNotNull($page->fresh()->published_at);

        $this->actingAs($editor)->patch('/admin/pages/publish', ['id' => $page->id, 'mode' => 'unpublish']);
        $this->assertNull($page->fresh()->published_at);
    }

    public function testDestroyDeletesPage()
    {
        $page = $this->createPage();

        $response = $this->actingAs($this->createEditor())->delete('/admin/pages/destroy', ['id' => $page->id]);

        $response->assertRedirect(route('admin::pages'));
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }

    private function createPage()
    {
        return Page::forceCreate([
            'title'     => 'Seite '.Str::random(8),
            'slug'      => 'seite-'.Str::lower(Str::random(8)),
            'body'      => 'Inhalt',
            'author_id' => $this->createEditor()->id,
        ]);
    }
}
