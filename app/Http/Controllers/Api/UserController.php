<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
        ]);

        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $query = User::query();

        if (! empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (! empty($validated['search'])) {
            $query->where(function ($builder) use ($validated) {
                $builder
                    ->where('name', 'like', '%'.$validated['search'].'%')
                    ->orWhere('email', 'like', '%'.$validated['search'].'%');
            });
        }

        $total = (clone $query)->count();
        $data = $query->orderBy('id')->forPage($page, $limit)->get();

        return response()->json(compact('data', 'total', 'page'));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['success' => true]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'excludeId' => ['nullable', 'integer'],
        ]);

        $exists = User::where('email', $validated['email'])
            ->when($validated['excludeId'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();

        return response()->json(['available' => ! $exists]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'oldPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['oldPassword'], $user->password)) {
            return response()->json(['message' => 'Password lama salah.'], 422);
        }

        $user->update(['password' => $validated['newPassword']]);

        return response()->json(['success' => true]);
    }
}
