<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display the user list.
     */
    public function index(): Response
    {
        return Inertia::render('admin/users/Index', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active', 'office_email']),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response
    {
        return Inertia::render('admin/users/Create');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        return Inertia::render('admin/users/Edit', [
            'user' => [
                ...$user->only('id', 'name', 'email', 'role', 'is_active', 'office_email'),
                'has_office_email_password' => $user->office_email_password !== null,
            ],
        ]);
    }

    /**
     * Update a user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (empty($data['office_email_password'])) {
            unset($data['office_email_password']);
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('admin.users.index');
    }

    /**
     * Deactivate a user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deactivated.')]);

        return to_route('admin.users.index');
    }
}
