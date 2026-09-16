<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventTotemController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameInstanceController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\TotemController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserGameController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/preview/{token}', [PreviewController::class, 'show']);
Route::post('/preview/{token}/approve', [PreviewController::class, 'approve']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/games', [GameController::class, 'store']);
        Route::put('/games/{game}', [GameController::class, 'update']);
        Route::delete('/games/{game}', [GameController::class, 'destroy']);
    });

    Route::apiResource('events', EventController::class);
    Route::apiResource('totems', TotemController::class);

    Route::get('/events/{event}/totems', [EventTotemController::class, 'index']);
    Route::post('/events/{event}/totems', [EventTotemController::class, 'store']);
    Route::delete('/events/{event}/totems/{totem}', [EventTotemController::class, 'destroy']);

    Route::get('/games', [GameController::class, 'index']);
    Route::get('/games/{game}', [GameController::class, 'show']);
    Route::post('/games/{game}/acquire', [GameController::class, 'acquire']);

    Route::get('/my-games', [UserGameController::class, 'index']);

    Route::get('/events/{event}/totems/{totem}/games', [GameInstanceController::class, 'index']);
    Route::post('/events/{event}/totems/{totem}/games', [GameInstanceController::class, 'store']);
    Route::put('/game-instances/{gameInstance}', [GameInstanceController::class, 'update']);
    Route::delete('/game-instances/{gameInstance}', [GameInstanceController::class, 'destroy']);
    Route::post('/game-instances/{gameInstance}/preview', [GameInstanceController::class, 'generatePreview']);

    Route::post('/uploads', [UploadController::class, 'store']);
});
