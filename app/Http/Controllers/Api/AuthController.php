<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'ok' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        $plainToken = Str::random(60);
        $user->forceFill(['api_token' => hash('sha256', $plainToken)])->save();

        return response()->json([
            'ok' => true,
            'user' => $user,
            'token' => $plainToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->forceFill(['api_token' => null])->save();

        return response()->json(['success' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
