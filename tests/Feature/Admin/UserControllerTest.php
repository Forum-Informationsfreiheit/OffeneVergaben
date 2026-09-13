<?php

namespace Tests\Feature\Admin;

use App\Role;
use App\User;
use Illuminate\Support\Facades\Hash;

class UserControllerTest extends AdminTestCase
{
    public function testIndexListsUsers()
    {
        $user = $this->createUser(Role::SUBSCRIBER);

        $response = $this->actingAs($this->createEditor())->get('/admin/users');

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.index');
        $response->assertSee($user->email);
    }

    public function testOnlyAdminsCanOpenTheCreateForm()
    {
        $this->actingAs($this->createEditor())->get('/admin/users/create')->assertForbidden();

        $response = $this->actingAs($this->createAdmin())->get('/admin/users/create');

        $response->assertStatus(200);
        $response->assertViewIs('admin.users.create');
    }

    public function testAdminCanStoreUser()
    {
        $response = $this->actingAs($this->createAdmin())->post('/admin/users/store', [
            'name'                  => 'Neue Redakteurin',
            'email'                 => 'redaktion@example.test',
            'password'              => 'geheim123',
            'password_confirmation' => 'geheim123',
            'role'                  => Role::EDITOR,
        ]);

        $response->assertRedirect(route('admin::users'));

        $user = User::where('email', 'redaktion@example.test')->first();
        $this->assertNotNull($user);
        $this->assertEquals(Role::EDITOR, $user->role_id);
        $this->assertTrue(Hash::check('geheim123', $user->password));
    }

    public function testStoreValidatesInput()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/users/store', [
            'email'                 => $admin->email, // already taken
            'password'              => 'geheim123',
            'password_confirmation' => 'anders123',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function testEditorCannotStoreUser()
    {
        $response = $this->actingAs($this->createEditor())->post('/admin/users/store', [
            'name'                  => 'Heimlicher Admin',
            'email'                 => 'heimlich@example.test',
            'password'              => 'geheim123',
            'password_confirmation' => 'geheim123',
            'role'                  => Role::ADMIN,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'heimlich@example.test']);
    }

    public function testEditorCanEditOwnProfileOnly()
    {
        $editor = $this->createEditor();
        $other  = $this->createEditor();

        $response = $this->actingAs($editor)->get('/admin/users/edit/'.$editor->id);
        $response->assertStatus(200);
        $response->assertViewIs('admin.users.edit');

        $this->actingAs($editor)->get('/admin/users/edit/'.$other->id)->assertForbidden();
    }

    public function testEditUnknownUserReturnsNotFound()
    {
        $this->actingAs($this->createAdmin())->get('/admin/users/edit/0')->assertNotFound();
    }

    public function testAdminCanUpdateUserIncludingRole()
    {
        $user = $this->createEditor();

        $response = $this->actingAs($this->createAdmin())->patch('/admin/users/update', [
            'id'    => $user->id,
            'name'  => 'Umbenannt',
            'email' => 'umbenannt@example.test',
            'role'  => Role::ADMIN,
        ]);

        $response->assertRedirect(route('admin::edit-user', $user->id));
        $this->assertDatabaseHas('users', [
            'id'      => $user->id,
            'name'    => 'Umbenannt',
            'email'   => 'umbenannt@example.test',
            'role_id' => Role::ADMIN,
        ]);
    }

    public function testEditorCanUpdateOwnProfileButNotOwnRole()
    {
        $editor = $this->createEditor();

        $this->actingAs($editor)->patch('/admin/users/update', [
            'id'    => $editor->id,
            'name'  => 'Neuer Name',
            'email' => $editor->email,
            'role'  => Role::ADMIN,
        ]);

        $this->assertDatabaseHas('users', ['id' => $editor->id, 'name' => 'Neuer Name', 'role_id' => Role::EDITOR]);
    }

    public function testEditorCannotUpdateOtherUsers()
    {
        $other = $this->createEditor();

        $response = $this->actingAs($this->createEditor())->patch('/admin/users/update', [
            'id'    => $other->id,
            'name'  => 'Übernommen',
            'email' => $other->email,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['id' => $other->id, 'name' => 'Übernommen']);
    }

    public function testUpdateChangesPasswordOnlyWhenGiven()
    {
        $user  = $this->createEditor();
        $admin = $this->createAdmin();
        $data  = ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => Role::EDITOR];

        $this->actingAs($admin)->patch('/admin/users/update', $data);
        $this->assertTrue(Hash::check('password', $user->fresh()->password)); // factory default

        $this->actingAs($admin)->patch('/admin/users/update', $data + [
            'password'              => 'geheim123',
            'password_confirmation' => 'geheim123',
        ]);
        $this->assertTrue(Hash::check('geheim123', $user->fresh()->password));
    }

    public function testAdminCanDeleteUser()
    {
        $user = $this->createUser(Role::SUBSCRIBER);

        $response = $this->actingAs($this->createAdmin())->delete('/admin/users/destroy', ['id' => $user->id]);

        $response->assertRedirect(route('admin::users'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function testRootUserCannotBeDeleted()
    {
        $response = $this->actingAs($this->createAdmin())->delete('/admin/users/destroy', ['id' => 1]);

        $response->assertRedirect(route('admin::users'));
        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function testEditorCannotDeleteUser()
    {
        $user = $this->createUser(Role::SUBSCRIBER);

        $response = $this->actingAs($this->createEditor())->delete('/admin/users/destroy', ['id' => $user->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
