<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Show the application users.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('admin/Users', [
            'users' => User::query()
                ->latest()
                ->get(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'created_at']),
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        (new User)->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_admin' => $request->boolean('is_admin'),
            'email_verified_at' => now(),
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    /**
     * Update an existing user.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->validated('password');
        }

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403);

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('admin.users.index');
    }
}
