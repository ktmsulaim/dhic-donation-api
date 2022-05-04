<?php

use App\Http\Controllers\api\LoginController;
use App\Http\Controllers\api\StudentController;
use App\Http\Controllers\api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [LoginController::class, 'user']);
    Route::post('logout', [LoginController::class, 'logout']);

    /*
|--------------------------------------------------------------------------
| Students
|--------------------------------------------------------------------------
|*/
    Route::get('students', [StudentController::class, 'index']);
    Route::get('students/{student}', [StudentController::class, 'show']);
    Route::post('students/import', [StudentController::class, 'import']);
    Route::post('students', [StudentController::class, 'store']);
    Route::put('students/{student}', [StudentController::class, 'update']);
    Route::delete('students/{student}', [StudentController::class, 'destroy']);

    Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscription/{subscription}/history', [SubscriptionController::class, 'makeHistory']);
});

Route::post("login", [LoginController::class, 'login']);
