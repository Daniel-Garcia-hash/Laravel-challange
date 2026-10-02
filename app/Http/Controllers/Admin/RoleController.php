<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::where('guard_name', 'web')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateName($request);
        Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return to_route('admin.roles.index')->with('success', 'Rol toegevoegd.');
    }

    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'web', 404);

        return view('admin.roles.form', compact('role'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        $data = $this->validateName($request, $role);
        if ($role->name === 'admin' && $data['name'] !== 'admin') {
            throw ValidationException::withMessages(['name' => 'De beheerrol admin kan niet worden hernoemd.']);
        }
        $role->update(['name' => $data['name'], 'guard_name' => 'web']);

        return to_route('admin.roles.index')->with('success', 'Rol bijgewerkt.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        if ($role->name === 'admin') {
            throw ValidationException::withMessages(['name' => 'De beheerrol admin kan niet worden verwijderd.']);
        }
        $role->delete();

        return to_route('admin.roles.index')->with('success', 'Rol verwijderd.');
    }

    private function validateName(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role)],
        ], [
            'name.required' => 'Vul een naam in.',
            'name.max' => 'De naam mag maximaal 255 tekens bevatten.',
            'name.unique' => 'Er bestaat al een rol met deze naam.',
        ]);
    }
}
