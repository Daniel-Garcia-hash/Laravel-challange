<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);
    }

    public function test_admin_can_create_read_update_and_delete_a_role(): void
    {
        $this->get('/admin/roles/create')->assertOk();
        $this->post('/admin/roles', ['name' => 'product aanpassen', 'guard_name' => 'api'])->assertRedirect('/admin/roles');
        $role = Role::findByName('product aanpassen', 'web');
        $this->get('/admin/roles')->assertOk()->assertSee('product aanpassen');
        $this->get("/admin/roles/{$role->id}/edit")->assertOk();
        $this->put("/admin/roles/{$role->id}", ['name' => 'game aanpassen'])->assertRedirect('/admin/roles');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'game aanpassen', 'guard_name' => 'web']);
        $this->delete("/admin/roles/{$role->id}")->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_names_are_required_unique_per_guard_and_limited_in_length(): void
    {
        $role = Role::create(['name' => 'bekijken', 'guard_name' => 'web']);
        $this->post('/admin/roles', ['name' => ' '])->assertSessionHasErrors('name');
        $this->post('/admin/roles', ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
        $this->post('/admin/roles', ['name' => 'bekijken'])->assertSessionHasErrors('name');
        $this->put("/admin/roles/{$role->id}", ['name' => 'bekijken'])->assertSessionHasNoErrors();
        Role::create(['name' => 'ander', 'guard_name' => 'api']);
        $this->post('/admin/roles', ['name' => 'ander'])->assertSessionHasNoErrors();
    }

    public function test_other_guards_cannot_be_edited_or_deleted(): void
    {
        $role = Role::create(['name' => 'api-only', 'guard_name' => 'api']);
        $this->get('/admin/roles')->assertDontSee('api-only');
        $this->get("/admin/roles/{$role->id}/edit")->assertNotFound();
        $this->put("/admin/roles/{$role->id}", ['name' => 'changed'])->assertNotFound();
        $this->delete("/admin/roles/{$role->id}")->assertNotFound();
    }

    public function test_all_role_routes_deny_guests_and_non_admins(): void
    {
        $role = Role::create(['name' => 'bekijken', 'guard_name' => 'web']);
        $requests = [
            ['GET', '/admin/roles'], ['GET', '/admin/roles/create'], ['POST', '/admin/roles'],
            ['GET', "/admin/roles/{$role->id}/edit"], ['PUT', "/admin/roles/{$role->id}"],
            ['DELETE', "/admin/roles/{$role->id}"],
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
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'bekijken']);
    }

    public function test_admin_role_cannot_be_renamed_or_deleted(): void
    {
        $role = Role::findByName('admin', 'web');
        $this->put("/admin/roles/{$role->id}", ['name' => 'beheerder'])->assertSessionHasErrors('name');
        $this->delete("/admin/roles/{$role->id}")->assertSessionHasErrors('name');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'admin']);
    }
}
