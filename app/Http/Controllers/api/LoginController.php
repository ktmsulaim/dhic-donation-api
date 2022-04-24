<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    //
    public function login(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if(!$user) {
                return response()->json([
                    'success' => false,
                    'data' => 'User not found'
                ], 404);
            }

            if(!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'data' => 'Invalid password'
                ], 401);
            }

            $token = $user->createToken('Donation')->plainTextToken;

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => $user
                ]
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
