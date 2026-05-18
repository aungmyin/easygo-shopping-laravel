<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::where('role', 'customer')
            ->when($request->search, fn($q, $s) => $q->where('name', 'like', "%{$s}%")
                                                     ->orWhere('email', 'like', "%{$s}%"))
            ->latest()
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function toggleActive(Request $request, User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return response()->json([
            'message'   => 'User status updated',
            'is_active' => $user->is_active,
        ]);
    }
}
