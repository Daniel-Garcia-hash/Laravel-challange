<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminAccess
{
    // Roep dit binnen een transactie aan. De rol-lock serialiseert adminverwijderingen.
    public static function ensureAnotherAdmin(User $user, string $field = 'role_id', string $bag = 'default'): void
    {
        $admin = Role::where('name', 'admin')->where('guard_name', 'web')->lockForUpdate()->first();
        if ($admin && $admin->users()->whereKey($user->id)->exists()
            && ! $admin->users()->where('users.id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                $field => 'De laatste admin kan niet worden verwijderd. Geef eerst een andere gebruiker de adminrol.',
            ])->errorBag($bag);
        }
    }
}
