<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffUserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $staff = User::query()->whereIn('role', array_keys(User::getAvailableRoles()))->paginate(20);
        return response()->json($staff);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8'],
            'role' => ['required', Rule::in(array_keys(User::getAvailableRoles()))],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $data = $request->validate([
            'name' => ['sometimes','string','max:255'],
            'email' => ['sometimes','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(array_keys(User::getAvailableRoles()))],
            'is_active' => ['sometimes','boolean'],
            'password' => ['sometimes','nullable','string','min:8'],
        ]);

        // Prevent self-demotion if admin updating own record
        if (array_key_exists('role', $data) && $user->id === auth()->id()) {
            abort_unless(auth()->user()->isAdmin(), 403);
            abort_unless($data['role'] === 'admin', 403);
        }

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data)->save();
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->noContent();
    }

    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);
        $user->is_active = !$user->is_active;
        $user->save();
        return response()->json(['is_active' => $user->is_active]);
    }

    public function setRole(Request $request, User $user)
    {
        $this->authorize('manageRoles', $user);
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(User::getAvailableRoles()))],
        ]);
        // Admin cannot demote themselves
        if ($user->id === auth()->id()) {
            abort_unless($data['role'] === 'admin', 403);
        }
        $user->role = $data['role'];
        $user->save();
        return response()->json($user);
    }
}
