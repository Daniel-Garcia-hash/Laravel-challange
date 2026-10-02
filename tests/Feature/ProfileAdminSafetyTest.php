<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileAdminSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_admin_cannot_delete_their_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh());
        $this->assertTrue($admin->fresh()->hasRole('admin', 'web'));
        $this->assertSame(1, User::role('admin', 'web')->count());
    }

    public function test_admin_can_delete_their_profile_when_another_admin_remains(): void
    {
        $role = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $remainingAdmin = User::factory()->create();
        $remainingAdmin->assignRole($role);

        $this->actingAs($admin)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($admin->fresh());
        $this->assertTrue($remainingAdmin->fresh()->hasRole('admin', 'web'));
        $this->assertSame(1, User::role('admin', 'web')->count());
    }
}
