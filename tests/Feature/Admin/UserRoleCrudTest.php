<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($this->admin);
    }

    public function test_crud_preserves_other_roles_and_changes_actual_page_access(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $customer = Role::findOrCreate('klant', 'web');
        $other = Role::findOrCreate('auditor', 'web');
        $permission = Permission::findOrCreate('product aanpassen', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($other);
        $this->actingAs($user)->get('/rechten/product-aanpassen')->assertForbidden();
        $this->actingAs($this->admin);
        $this->get('/admin/user-roles/create')->assertOk()->assertSee($user->email);
        $this->post('/admin/user-roles', ['role_id' => $role->id, 'user_id' => $user->id, 'model_type' => 'FakeModel'])
            ->assertRedirect('/admin/user-roles');
        $this->assertDatabaseHas('model_has_roles', ['role_id' => $role->id, 'model_id' => $user->id, 'model_type' => User::class]);
        $this->get('/admin/user-roles')->assertOk()->assertSee($user->email)->assertSee('editor');
        $uri = "/admin/user-roles/{$role->id}/{$user->id}";
        $this->get("$uri/edit")->assertOk();
        $this->actingAs($user->fresh())->get('/rechten/product-aanpassen')->assertOk();
        $this->actingAs($this->admin);
        $this->put($uri, ['role_id' => $customer->id, 'user_id' => $user->id])->assertRedirect('/admin/user-roles');
        $this->assertFalse($user->fresh()->hasRole($role));
        $this->assertTrue($user->fresh()->hasRole($other));
        $this->actingAs($user->fresh())->get('/rechten/product-aanpassen')->assertForbidden();
        $this->actingAs($this->admin);
        $this->delete("/admin/user-roles/{$customer->id}/{$user->id}")->assertRedirect('/admin/user-roles');
        $this->assertTrue($user->fresh()->hasRole($other));
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('roles', ['id' => $customer->id]);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }

    public function test_invalid_duplicate_and_non_web_selections_leave_existing_roles_intact(): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('editor', 'web');
        $other = Role::findOrCreate('klant', 'web');
        $api = Role::findOrCreate('api', 'api');
        $user->assignRole([$role, $other]);
        $pair = ['role_id' => $other->id, 'user_id' => $user->id];
        $this->post('/admin/user-roles', $pair)->assertSessionHasErrors('role_id');
        $this->put("/admin/user-roles/{$role->id}/{$user->id}", $pair)->assertSessionHasErrors('role_id');
        $this->post('/admin/user-roles', [])->assertSessionHasErrors(['role_id', 'user_id']);
        $this->post('/admin/user-roles', ['role_id' => 999, 'user_id' => 999])->assertSessionHasErrors(['role_id', 'user_id']);
        $this->post('/admin/user-roles', ['role_id' => $api->id, 'user_id' => $user->id])->assertSessionHasErrors('role_id');
        $this->put("/admin/user-roles/{$role->id}/{$user->id}", ['role_id' => $role->id, 'user_id' => $user->id])
            ->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasAllRoles([$role, $other]));
        $this->get("/admin/user-roles/{$api->id}/{$user->id}/edit")->assertNotFound();
        $this->delete("/admin/user-roles/{$role->id}/999")->assertNotFound();
    }

    public function test_last_admin_cannot_be_demoted_or_detached(): void
    {
        $adminRole = Role::findByName('admin', 'web');
        $customer = Role::findOrCreate('klant', 'web');
        $uri = "/admin/user-roles/{$adminRole->id}/{$this->admin->id}";
        $this->put($uri, ['role_id' => $customer->id, 'user_id' => $this->admin->id])->assertSessionHasErrors('role_id');
        $this->delete($uri)->assertSessionHasErrors('role_id');
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_admin_can_detach_self_when_another_admin_remains(): void
    {
        $adminRole = Role::findByName('admin', 'web');
        $second = User::factory()->create();
        $second->assignRole($adminRole);
        $this->delete("/admin/user-roles/{$adminRole->id}/{$this->admin->id}")->assertRedirect('/dashboard');
        $this->actingAs($this->admin->fresh())->get('/admin/user-roles')->assertForbidden();
        $this->assertTrue($second->fresh()->hasRole('admin'));
    }

    public function test_deleting_role_cleans_links_without_deleting_users_or_permissions(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('bewerken', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->delete("/admin/roles/{$role->id}")->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('model_has_roles', ['role_id' => $role->id]);
        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => $role->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
    }
}
