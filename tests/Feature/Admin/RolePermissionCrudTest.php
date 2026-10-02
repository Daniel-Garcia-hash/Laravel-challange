<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_crud_changes_only_selected_link_and_immediately_changes_permission(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $newRole = Role::findOrCreate('klant', 'web');
        $permission = Permission::findOrCreate('product aanpassen', 'web');
        $other = Permission::findOrCreate('product bekijken', 'web');
        $role->givePermissionTo($other);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->assertFalse($user->fresh()->can('product aanpassen'));
        $this->get('/admin/role-permissions/create')->assertOk();
        $this->post('/admin/role-permissions', ['role_id' => $role->id, 'permission_id' => $permission->id])
            ->assertRedirect('/admin/role-permissions');
        $this->assertTrue($user->fresh()->can('product aanpassen'));
        $this->get('/admin/role-permissions')->assertOk()->assertSee('editor')->assertSee('product aanpassen');
        $uri = "/admin/role-permissions/{$role->id}/{$permission->id}";
        $this->get("$uri/edit")->assertOk();
        $this->put($uri, ['role_id' => $newRole->id, 'permission_id' => $permission->id])
            ->assertRedirect('/admin/role-permissions');
        $this->assertFalse($user->fresh()->can('product aanpassen'));
        $this->assertDatabaseHas('role_has_permissions', ['role_id' => $role->id, 'permission_id' => $other->id]);
        $this->assertDatabaseHas('role_has_permissions', ['role_id' => $newRole->id, 'permission_id' => $permission->id]);
        $this->delete("/admin/role-permissions/{$newRole->id}/{$permission->id}")->assertRedirect('/admin/role-permissions');
        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => $newRole->id, 'permission_id' => $permission->id]);
        $this->assertDatabaseHas('roles', ['id' => $newRole->id]);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_duplicates_invalid_ids_and_non_web_guards_are_rejected_without_losing_old_link(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $first = Permission::findOrCreate('first', 'web');
        $second = Permission::findOrCreate('second', 'web');
        $role->givePermissionTo([$first, $second]);
        $pair = ['role_id' => $role->id, 'permission_id' => $second->id];
        $this->post('/admin/role-permissions', $pair)->assertSessionHasErrors('permission_id');
        $this->put("/admin/role-permissions/{$role->id}/{$first->id}", $pair)->assertSessionHasErrors('permission_id');
        $this->assertDatabaseCount('role_has_permissions', 2);
        $this->post('/admin/role-permissions', [])->assertSessionHasErrors(['role_id', 'permission_id']);
        $this->post('/admin/role-permissions', ['role_id' => 999, 'permission_id' => 999])
            ->assertSessionHasErrors(['role_id', 'permission_id']);
        $apiRole = Role::findOrCreate('api', 'api');
        $apiPermission = Permission::findOrCreate('api', 'api');
        $this->post('/admin/role-permissions', ['role_id' => $apiRole->id, 'permission_id' => $apiPermission->id])
            ->assertSessionHasErrors(['role_id', 'permission_id']);
        $this->put("/admin/role-permissions/{$role->id}/{$first->id}", ['role_id' => $role->id, 'permission_id' => $first->id])
            ->assertSessionHasNoErrors();
        $this->get("/admin/role-permissions/{$apiRole->id}/{$first->id}/edit")->assertNotFound();
        $this->delete("/admin/role-permissions/{$role->id}/999")->assertNotFound();
    }

    public function test_removing_permission_removes_pivot_and_access(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('product aanpassen', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->assertTrue($user->fresh()->can('product aanpassen'));
        $this->delete("/admin/permissions/{$permission->id}")->assertRedirect('/admin/permissions');
        $this->assertFalse($user->fresh()->can('product aanpassen'));
        $this->assertDatabaseCount('role_has_permissions', 0);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
