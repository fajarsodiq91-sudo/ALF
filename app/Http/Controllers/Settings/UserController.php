<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->with('roles')->orderBy('name')->get();

        return view('erp.settings.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('erp.settings.users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('settings.users')->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        return view('erp.settings.users.edit', ['user' => $user, 'roles' => Role::orderBy('name')->get()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', Password::defaults()],
            'role' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $isActive = $request->boolean('is_active');

        if ($user->is(auth()->user()) && ! $isActive) {
            return back()->withInput()->with('error', 'You cannot deactivate your own account.');
        }

        if ($this->isLastActiveSuperAdmin($user) && ($data['role'] !== 'Super Admin' || ! $isActive)) {
            return back()->withInput()->with('error', 'At least one active Super Admin must remain.');
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $isActive,
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();
        $user->syncRoles([$data['role']]);

        return redirect()->route('settings.users')->with('status', 'User updated successfully.');
    }

    private function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->is_active
            && $user->hasRole('Super Admin')
            && User::role('Super Admin')->where('is_active', true)->count() <= 1;
    }
}
