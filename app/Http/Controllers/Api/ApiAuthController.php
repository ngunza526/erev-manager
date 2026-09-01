<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'actif') {
            throw ValidationException::withMessages(['email' => 'Identifiants API invalides.']);
        }

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $user->createToken($data['device_name'] ?? 'api-client')->plainTextToken,
            'user' => $user->only('id', 'name', 'email', 'level', 'church_id', 'community_id'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token API revoque.']);
    }
}
