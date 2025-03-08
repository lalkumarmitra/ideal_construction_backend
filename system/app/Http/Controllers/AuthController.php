<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function login(LoginRequest $request) {
        return $this->tryCatchWrapper(function () use ($request) {
            $username = $request->username;
            $password = $request->password;
            // Prepare credentials array for Auth::attempt
            $credentials = [];
            if (!($user = User::where('email', $username)->orWhere('phone', $username)->first())) throw ValidationException::withMessages([
                'username' => ['Invalid username or password.'],
            ]);
            $credentials[$user->email === $username ? 'email' : 'phone'] = $username;
            $credentials['password'] = $password;
            if (!Auth::attempt($credentials)) {
                throw ValidationException::withMessages([
                    'username' => ['Invalid username or password.'],
                ]);
            }
            $user = User::find(Auth::id());
            if (!$user->is_active) throw new \Exception('Account is deactivated.', 403);
            if ($user->is_blocked) throw new \Exception('Account is blocked.', 403);
            // generating token an sending response
            $token = $user->createToken('user-auth-token')->plainTextToken;
            return [
                'message' => 'Login successful',
                '_token' => $token,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'avatar' => $user->avatar,
                        'role' => $user->role,
                    ],
                ],
            ];
        });
    }
    public function validate_token(){
        return $this->tryCatchWrapper(function(){
            if(!($user = Auth::user())) throw new \Exception('Unauthorized , Please login again',401);
            return [
                'message'=>'validation successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'avatar' => $user->avatar,
                        'role' => $user->role,
                    ],
                ],
            ];
        });
    }
    public function logout(Request $request) {
        return $this->tryCatchWrapper(function () use ($request) {
            $user = Auth::user();
            if ($user) $user->currentAccessToken()->delete();
            return [
                'message' => 'Logout successful',
            ];
        });
    }

}