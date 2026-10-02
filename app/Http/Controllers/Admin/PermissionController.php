<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', [
            'permissions' => Permission::where('guard_name', 'web')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.permissions.form', ['permission' => new Permission]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateName($request);
        Permission::create(['name' => $data['name'], 'guard_name' => 'web']);

        return to_route('admin.permissions.index')->with('success', 'Permissie toegevoegd.');
    }

    public function edit(Permission $permission): View
    {
        abort_unless($permission->guard_name === 'web', 404);

        return view('admin.permissions.form', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'web', 404);
        $data = $this->validateName($request, $permission);
        $permission->update(['name' => $data['name'], 'guard_name' => 'web']);

        return to_route('admin.permissions.index')->with('success', 'Permissie bijgewerkt.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'web', 404);
        $permission->delete();

        return to_route('admin.permissions.index')->with('success', 'Permissie verwijderd.');
    }

    private function validateName(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission)],
        ], [
            'name.required' => 'Vul een naam in.',
            'name.max' => 'De naam mag maximaal 255 tekens bevatten.',
            'name.unique' => 'Er bestaat al een permissie met deze naam.',
        ]);
    }
}
