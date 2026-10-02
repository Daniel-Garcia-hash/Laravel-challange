<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_makes_an_existing_user_admin_once(): void
    {
        $user = User::factory()->create(['email' => 'beheerder@example.com']);

        $this->artisan('app:make-admin', ['email' => $user->email])
            ->expectsOutput('Gebruiker beheerder@example.com heeft nu de adminrol.')
            ->assertExitCode(0);
        $this->artisan('app:make-admin', ['email' => $user->email])->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('admin', 'web'));
        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => Role::findByName('admin', 'web')->id,
            'model_id' => $user->id,
            'model_type' => $user->getMorphClass(),
        ]);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('model_has_roles', 1);
    }

    public function test_command_rejects_unknown_email_without_creating_accounts(): void
    {
        $this->artisan('app:make-admin', ['email' => 'onbekend@example.com'])
            ->expectsOutput('Geen geregistreerde gebruiker gevonden met dit e-mailadres.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('model_has_roles', 0);
    }

    public function test_seeder_is_idempotent_and_creates_no_accounts(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseCount('permissions', 4);
        $this->assertDatabaseCount('role_has_permissions', 7);
        $this->assertDatabaseCount('model_has_roles', 0);

        $this->assertEqualsCanonicalizing(
            ['product bekijken'],
            Role::findByName('klant', 'web')->permissions->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['product bekijken', 'product aanpassen'],
            Role::findByName('editor', 'web')->permissions->pluck('name')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['product bekijken', 'product invoeren', 'product aanpassen', 'product verwijderen'],
            Role::findByName('admin', 'web')->permissions->pluck('name')->all()
        );
        $this->assertDatabaseMissing('roles', ['guard_name' => 'api']);
        $this->assertDatabaseMissing('permissions', ['guard_name' => 'api']);
    }

    public function test_registration_does_not_grant_admin_even_if_requested(): void
    {
        $this->seed();

        $this->post('/register', [
            'name' => 'Nieuwe gebruiker',
            'email' => 'nieuw@example.com',
            'password' => 'EenSterkWachtwoord123!',
            'password_confirmation' => 'EenSterkWachtwoord123!',
            'role' => 'admin',
            'roles' => ['admin'],
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'nieuw@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasRole('admin', 'web'));
        $this->assertDatabaseCount('model_has_roles', 0);
    }
}
