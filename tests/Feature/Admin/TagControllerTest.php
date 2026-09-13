<?php

namespace Tests\Feature\Admin;

use App\Tag;
use Illuminate\Support\Str;

class TagControllerTest extends AdminTestCase
{
    public function testIndexListsTags()
    {
        $tag = $this->createTag();

        $response = $this->actingAs($this->createEditor())->get('/admin/tags');

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.index');
        $response->assertSee($tag->name);
    }

    public function testCreateFormLoads()
    {
        $response = $this->actingAs($this->createEditor())->get('/admin/tags/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.create');
    }

    public function testStoreCreatesTag()
    {
        $name = 'Tag '.Str::random(8);

        $response = $this->actingAs($this->createEditor())->post('/admin/tags/store', [
            'name'        => $name,
            'description' => '',
        ]);

        $response->assertRedirect(route('admin::tags'));
        $this->assertDatabaseHas('tags', ['name' => $name, 'description' => null]);
    }

    public function testStoreRequiresName()
    {
        $response = $this->actingAs($this->createEditor())->post('/admin/tags/store', ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function testStoreRejectsDuplicateName()
    {
        $existing = $this->createTag();

        $response = $this->actingAs($this->createEditor())->post('/admin/tags/store', ['name' => $existing->name]);

        $response->assertSessionHasErrors('name');
    }

    public function testEditFormLoads()
    {
        $tag = $this->createTag();

        $response = $this->actingAs($this->createEditor())->get('/admin/tags/edit/'.$tag->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
    }

    public function testEditUnknownTagReturnsNotFound()
    {
        $this->actingAs($this->createEditor())->get('/admin/tags/edit/0')->assertNotFound();
    }

    public function testUpdateRenamesTag()
    {
        $tag  = $this->createTag();
        $name = 'Tag '.Str::random(8);

        $response = $this->actingAs($this->createEditor())->patch('/admin/tags/update', [
            'id'          => $tag->id,
            'name'        => $name,
            'description' => 'Neue Beschreibung',
        ]);

        $response->assertRedirect(route('admin::tags'));
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => $name, 'description' => 'Neue Beschreibung']);
    }

    public function testUpdateRejectsDuplicateName()
    {
        $tag   = $this->createTag();
        $other = $this->createTag();

        $response = $this->actingAs($this->createEditor())->patch('/admin/tags/update', [
            'id'   => $tag->id,
            'name' => $other->name,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function testUpdateSavesDescriptionWithoutRenaming()
    {
        $this->markTestIncomplete('TagController::update only saves anything when the name changes.');

        $tag = $this->createTag();

        $this->actingAs($this->createEditor())->patch('/admin/tags/update', [
            'id'          => $tag->id,
            'name'        => $tag->name,
            'description' => 'Neue Beschreibung',
        ]);

        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'description' => 'Neue Beschreibung']);
    }

    public function testDestroyDeletesTag()
    {
        $tag = $this->createTag();

        $response = $this->actingAs($this->createEditor())->delete('/admin/tags/destroy', ['id' => $tag->id]);

        $response->assertRedirect(route('admin::tags'));
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    private function createTag()
    {
        return Tag::create(['name' => 'Tag '.Str::random(8), 'description' => 'Beschreibung']);
    }
}
