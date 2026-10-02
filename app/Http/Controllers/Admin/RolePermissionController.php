<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        $links = DB::table('role_has_permissions')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('roles.guard_name', 'web')->where('permissions.guard_name', 'web')
            ->select('roles.id as role_id', 'roles.name as role_name',
                'permissions.id as permission_id', 'permissions.name as permission_name')
            ->orderBy('roles.name')->orderBy('permissions.name')->paginate(20);

        return view('admin.role-permissions.index', compact('links'));
    }

    public function create(): View
    {
        return $this->form();
    }

    public function store(Request $request): RedirectResponse
    {
        [$role, $permission] = $this->selectedRecords($request);
        $this->rejectDuplicate($role, $permission);
        $role->givePermissionTo($permission);

        return to_route('admin.role-permissions.index')->with('success', 'Permissie aan rol gekoppeld.');
    }

    public function edit(Role $role, Permission $permission): View
    {
        $this->assertLinkExists($role, $permission);

        return $this->form($role, $permission);
    }

    public function update(Request $request, Role $role, Permission $permission): RedirectResponse
    {
        $this->assertLinkExists($role, $permission);
        [$newRole, $newPermission] = $this->selectedRecords($request);
        if ($newRole->id !== $role->id || $newPermission->id !== $permission->id) {
            $this->rejectDuplicate($newRole, $newPermission);
            // De combinatie van twee IDs identificeert de koppeling. Er is geen pivot-id.
            DB::transaction(function () use ($role, $permission, $newRole, $newPermission) {
                $role->revokePermissionTo($permission);
                $newRole->givePermissionTo($newPermission);
            });
        }

        return to_route('admin.role-permissions.index')->with('success', 'Koppeling bijgewerkt.');
    }

    public function destroy(Role $role, Permission $permission): RedirectResponse
    {
        $this->assertLinkExists($role, $permission);
        $role->revokePermissionTo($permission);

        return to_route('admin.role-permissions.index')->with('success', 'Permissie van rol ontkoppeld.');
    }

    private function form(?Role $role = null, ?Permission $permission = null): View
    {
        return view('admin.role-permissions.form', [
            'role' => $role, 'permission' => $permission,
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    private function selectedRecords(Request $request): array
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'permission_id' => ['required', 'integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ], [
            'role_id.required' => 'Kies een rol.', 'role_id.exists' => 'Kies een bestaande rol met guard web.',
            'permission_id.required' => 'Kies een permissie.', 'permission_id.exists' => 'Kies een bestaande permissie met guard web.',
        ]);

        return [Role::findOrFail($data['role_id']), Permission::findOrFail($data['permission_id'])];
    }

    private function assertLinkExists(Role $role, Permission $permission): void
    {
        abort_unless($role->guard_name === 'web' && $permission->guard_name === 'web'
            && $role->permissions()->whereKey($permission->id)->exists(), 404);
    }

    private function rejectDuplicate(Role $role, Permission $permission): void
    {
        if ($role->permissions()->whereKey($permission->id)->exists()) {
            throw ValidationException::withMessages(['permission_id' => 'Deze permissie is al aan deze rol gekoppeld.']);
        }
    }
}
