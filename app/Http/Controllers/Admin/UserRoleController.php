<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index(): View
    {
        $links = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('roles.guard_name', 'web')
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->select('roles.id as role_id', 'roles.name as role_name',
                'users.id as user_id', 'users.name as user_name', 'users.email')
            ->orderBy('users.name')->orderBy('roles.name')->paginate(20);

        return view('admin.user-roles.index', compact('links'));
    }

    public function create(): View
    {
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        [$role, $user] = $this->selectedRecords($request);
        $this->rejectDuplicate($role, $user);
        $user->assignRole($role);

        return to_route('admin.user-roles.index')->with('success', 'Rol aan gebruiker gekoppeld.');
    }

    public function edit(Role $role, User $user): View
    {
        $this->assertLinkExists($role, $user);

        return $this->form($role, $user);
    }

    public function update(Request $request, Role $role, User $user): RedirectResponse
    {
        $this->assertLinkExists($role, $user);
        [$newRole, $newUser] = $this->selectedRecords($request);
        if ($newRole->id !== $role->id || $newUser->id !== $user->id) {
            $this->rejectDuplicate($newRole, $newUser);
            DB::transaction(function () use ($role, $user, $newRole, $newUser) {
                if ($role->name === 'admin' && $newRole->name !== 'admin') {
                    AdminAccess::ensureAnotherAdmin($user);
                }
                $user->removeRole($role);
                $newUser->assignRole($newRole);
            });
        }

        return $this->afterChange($request, 'Koppeling bijgewerkt.');
    }

    public function destroy(Request $request, Role $role, User $user): RedirectResponse
    {
        $this->assertLinkExists($role, $user);
        DB::transaction(function () use ($role, $user) {
            if ($role->name === 'admin') {
                AdminAccess::ensureAnotherAdmin($user);
            }
            $user->removeRole($role);
        });

        return $this->afterChange($request, 'Rol van gebruiker ontkoppeld.');
    }

    private function afterChange(Request $request, string $message): RedirectResponse
    {
        // Als de ingelogde admin zichzelf ontkoppelt, krijgt hij meteen zijn eigen dashboard.
        $stillAdmin = $request->user()->fresh()->hasRole('admin', 'web');

        return to_route($stillAdmin ? 'admin.user-roles.index' : 'dashboard')->with('success', $message);
    }

    private function form(?Role $role = null, ?User $user = null): View
    {
        return view('admin.user-roles.form', [
            'role' => $role, 'user' => $user,
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'users' => User::orderBy('name')->orderBy('email')->get(),
            'modelType' => (new User)->getMorphClass(),
        ]);
    }

    private function selectedRecords(Request $request): array
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ], [
            'role_id.required' => 'Kies een rol.', 'role_id.exists' => 'Kies een bestaande rol met guard web.',
            'user_id.required' => 'Kies een gebruiker.', 'user_id.exists' => 'Kies een bestaande gebruiker.',
        ]);

        return [Role::findOrFail($data['role_id']), User::findOrFail($data['user_id'])];
    }

    private function assertLinkExists(Role $role, User $user): void
    {
        abort_unless($role->guard_name === 'web' && $user->roles()->whereKey($role->id)->exists(), 404);
    }

    private function rejectDuplicate(Role $role, User $user): void
    {
        if ($user->roles()->whereKey($role->id)->exists()) {
            throw ValidationException::withMessages(['role_id' => 'Deze gebruiker heeft deze rol al.']);
        }
    }
}
