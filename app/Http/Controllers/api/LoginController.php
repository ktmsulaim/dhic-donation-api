<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    //
    public function login(Request $request)
    {
        try {
            return response()->json($request->all());
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
