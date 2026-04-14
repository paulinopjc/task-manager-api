<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function listUsers(): JsonResponse
    {
        Gate::authorize('admin');

        return response()->json([
            'data' => UserResource::collection(User::orderBy('name')->get()),
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => UserRole::from($validated['role']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created.',
        ], 201);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => ['sometimes', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['role'])) {
            $validated['role'] = UserRole::from($validated['role']);
        }

        $user->update($validated);

        return response()->json([
            'data' => new UserResource($user->refresh()),
            'message' => 'User updated.',
        ]);
    }

    public function toggleUser(User $user): JsonResponse
    {
        Gate::authorize('admin');

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => $user->is_active ? 'User activated.' : 'User deactivated.',
        ]);
    }
}
