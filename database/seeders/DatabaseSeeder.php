<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['product bekijken', 'product invoeren', 'product aanpassen', 'product verwijderen'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('klant', 'web')->syncPermissions(['product bekijken']);
        Role::findOrCreate('editor', 'web')->syncPermissions(['product bekijken', 'product aanpassen']);
        Role::findOrCreate('admin', 'web')->syncPermissions(
            Permission::query()->where('guard_name', 'web')->get()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
