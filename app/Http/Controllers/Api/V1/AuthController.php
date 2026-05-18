<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user  = User::create($data);
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data'    => new UserResource($user),
            'token'   => $token,
            'message' => 'Registered successfully',
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $user  = Auth::user();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'data'    => new UserResource($user),
            'token'   => $token,
            'message' => 'Login successful',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => new UserResource($request->user())]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'  => 'sometimes|string',
            'phone' => 'nullable|string',
        ]);
        $request->user()->update($data);
        return response()->json([
            'data'    => new UserResource($request->user()),
            'message' => 'Profile updated',
        ]);
    }
}
