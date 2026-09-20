<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Cannot be renamed or deleted; Super Admin also cannot have its permissions edited. */
    private const BUILT_IN = ['Super Admin', 'Finance', 'Staff', 'Viewer'];

    public function index(): View
    {
        $roles = Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('erp.settings.roles.index', ['roles' => $roles, 'builtIn' => self::BUILT_IN]);
    }

    public function create(): View
    {
        return view('erp.settings.roles.create', ['permissions' => $this->groupedPermissions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('settings.roles')->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View|RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            return redirect()->route('settings.roles')->with('error', 'Super Admin always has every permission and cannot be edited.');
        }

        return view('erp.settings.roles.edit', [
            'role' => $role,
            'permissions' => $this->groupedPermissions(),
            'locked' => in_array($role->name, self::BUILT_IN, true),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            return redirect()->route('settings.roles')->with('error', 'Super Admin always has every permission and cannot be edited.');
        }

        $locked = in_array($role->name, self::BUILT_IN, true);

        $data = $request->validate([
            'name' => $locked ? ['prohibited'] : ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        if (! $locked) {
            $role->update(['name' => $data['name']]);
        }

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('settings.roles')->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, self::BUILT_IN, true)) {
            return redirect()->route('settings.roles')->with('error', 'Built-in roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()->route('settings.roles')->with('error', 'This role is assigned to users. Reassign them first.');
        }

        $role->delete();

        return redirect()->route('settings.roles')->with('status', 'Role deleted successfully.');
    }

    private function groupedPermissions()
    {
        return Permission::orderBy('name')->get()->groupBy(
            fn ($permission) => str_contains($permission->name, '.') ? explode('.', $permission->name)[0] : 'general'
        );
    }
}
