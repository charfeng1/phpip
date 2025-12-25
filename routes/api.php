<?php

use App\Http\Controllers\Api\ApiTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('token', [ApiTokenController::class, 'store'])->middleware('throttle:api-auth');

    Route::middleware(['auth:api', 'throttle:api'])->group(function () {
        Route::get('me', [ApiTokenController::class, 'show']);
        Route::post('logout', [ApiTokenController::class, 'destroy']);
    });
});

Route::middleware(['auth:api', 'throttle:api'])->get('/ping', function (Request $request) {
    return response()->json([
        'status' => 'ok',
        'login' => $request->user()->login,
    ]);
});
