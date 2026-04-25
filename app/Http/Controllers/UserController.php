<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index()
    {
        $users = User::with('roles')
            ->orderBy('id')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'created_at' => $user->created_at->format('Y-m-d'),
            ]);

        $roles = Role::select('id', 'name')->get();

        return Inertia::render('users/Index', compact('users', 'roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    public function edit(User $user)
    {
        $roles = Role::select('id', 'name')->get();

        return Inertia::render('users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'roles' => $roles,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    public function destroy(User $user)
    {
        try {
            $this->userService->delete($user);
        } catch (\DomainException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}
