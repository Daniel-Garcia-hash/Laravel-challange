<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_admin_can_create_read_update_and_delete_a_permission(): void
    {
        $this->get('/admin/permissions/create')->assertOk();
        $this->post('/admin/permissions', ['name' => 'product aanpassen', 'guard_name' => 'api'])->assertRedirect('/admin/permissions');
        $permission = Permission::findByName('product aanpassen', 'web');
        $this->get('/admin/permissions')->assertOk()->assertSee('product aanpassen');
        $this->get("/admin/permissions/{$permission->id}/edit")->assertOk();
        $this->put("/admin/permissions/{$permission->id}", ['name' => 'game aanpassen'])->assertRedirect('/admin/permissions');
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'game aanpassen', 'guard_name' => 'web']);
        $this->delete("/admin/permissions/{$permission->id}")->assertRedirect('/admin/permissions');
        $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
    }

    public function test_names_are_required_unique_per_guard_and_limited_in_length(): void
    {
        $permission = Permission::create(['name' => 'bekijken', 'guard_name' => 'web']);
        $this->post('/admin/permissions', ['name' => ' '])->assertSessionHasErrors('name');
        $this->post('/admin/permissions', ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
        $this->post('/admin/permissions', ['name' => 'bekijken'])->assertSessionHasErrors('name');
        $this->put("/admin/permissions/{$permission->id}", ['name' => 'bekijken'])->assertSessionHasNoErrors();
        Permission::create(['name' => 'ander', 'guard_name' => 'api']);
        $this->post('/admin/permissions', ['name' => 'ander'])->assertSessionHasNoErrors();
    }

    public function test_other_guards_cannot_be_edited_or_deleted(): void
    {
        $permission = Permission::create(['name' => 'api-only', 'guard_name' => 'api']);
        $this->get('/admin/permissions')->assertDontSee('api-only');
        $this->get("/admin/permissions/{$permission->id}/edit")->assertNotFound();
        $this->put("/admin/permissions/{$permission->id}", ['name' => 'changed'])->assertNotFound();
        $this->delete("/admin/permissions/{$permission->id}")->assertNotFound();
    }

    public function test_all_permission_routes_deny_guests_and_non_admins(): void
    {
        $permission = Permission::create(['name' => 'bekijken', 'guard_name' => 'web']);
        $requests = [
            ['GET', '/admin/permissions'], ['GET', '/admin/permissions/create'], ['POST', '/admin/permissions'],
            ['GET', "/admin/permissions/{$permission->id}/edit"], ['PUT', "/admin/permissions/{$permission->id}"],
            ['DELETE', "/admin/permissions/{$permission->id}"],
        ];
        auth()->logout();
        foreach ($requests as [$method, $uri]) {
            $this->call($method, $uri, ['name' => 'changed'])->assertRedirect('/login');
        }
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('klant', 'web'));
        $this->actingAs($customer);
        foreach ($requests as [$method, $uri]) {
            $this->call($method, $uri, ['name' => 'changed'])->assertForbidden();
        }
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'bekijken']);
    }
}
