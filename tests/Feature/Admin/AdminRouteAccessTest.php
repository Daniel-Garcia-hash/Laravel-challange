<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_route_and_http_method_is_protected(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('product aanpassen', 'web');
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);
        $requests = [];
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getName() ?? '', 'admin.')) {
                continue;
            }
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
            $uri = '/'.str_replace(['{role}', '{permission}', '{user}'], [$role->id, $permission->id, $user->id], $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $requests[] = [$method, $uri];
            }
        }
        $this->assertNotEmpty($requests);
        foreach ($requests as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect('/login');
        }
        $this->actingAs($user);
        foreach ($requests as [$method, $uri]) {
            $this->call($method, $uri)->assertForbidden();
        }
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('permissions', 1);
        $this->assertDatabaseCount('role_has_permissions', 1);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public function test_menu_has_four_admin_links_in_dashboard_and_both_profile_menus(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        $this->actingAs($admin);
        $dashboard = $this->get('/dashboard')->assertOk();
        $profile = $this->get('/profile')->assertOk();
        foreach (['permissions', 'roles', 'role-permissions', 'user-roles'] as $resource) {
            $url = route('admin.'.$resource.'.index');
            $dashboard->assertSee($url, false);
            $this->assertSame(2, substr_count($profile->getContent(), 'href="'.$url.'"'));
        }
        $this->actingAs(User::factory()->create());
        $customerDashboard = $this->get('/dashboard')->assertOk();
        foreach (['permissions', 'roles', 'role-permissions', 'user-roles'] as $resource) {
            $customerDashboard->assertDontSee(route('admin.'.$resource.'.index'), false);
        }
    }
}
