<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private const FULL_ACCESS = ['books:read', 'books:write'];

    private const READ_ONLY = ['books:read'];

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return $this->tokenResponse($user, 'full-access', self::FULL_ACCESS, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // Same message for "no such email" and "wrong password" so nobody can probe for accounts.
        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        return $this->tokenResponse($user, 'full-access', self::FULL_ACCESS);
    }

    public function readOnlyToken(Request $request): JsonResponse
    {
        return $this->tokenResponse($request->user(), 'read-only', self::READ_ONLY, 201);
    }

    public function logout(Request $request): JsonResponse
    {
        // Deletes only the token used for this request (its row in personal_access_tokens).
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function tokenResponse(User $user, string $name, array $abilities, int $status = 200): JsonResponse
    {
        $token = $user->createToken($name, $abilities);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'abilities' => $abilities,
            ],
        ], $status);
    }
}
