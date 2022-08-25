<?php

namespace App\Http\Controllers\API\Auth;

use stdClass;
use App\Models\User;
use App\Models\PickList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthUserController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required'],
            'password' => ['required']
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect']
            ]);
        } else if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided credentials are incorrect']
            ]);
        }

        $token = $user->createToken('authToken');
        $accessToken = $token->plainTextToken;

        $request->user = $user;

        return responseSuccess([
            'access_token' => $accessToken,
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request...
        $request->user()->currentAccessToken()->delete();

        return responseSuccess(['message' => 'Logged out successfully']);
    }

    public function getMe(Request $request)
    {
        $authUser = $request->user();
        $authUser->makeHidden([
            'password_changed',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
        return response()->json($authUser);
    }
}
