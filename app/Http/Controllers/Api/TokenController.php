<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    // POST /api/v1/login
    // Checks email and password, then returns a new token for this device.
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $request->email)->first();

        // Same message for "no such user" and "wrong password", so the API
        // doesn't reveal which emails have accounts.
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Only a hash is stored; this is the one time the plain token is shown.
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    // POST /api/v1/logout
    // Deletes the token used for this request. Other devices stay logged in.
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
