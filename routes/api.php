<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactRequestController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventTotemController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\GameInstanceController;
use App\Http\Controllers\Api\GamePlayController;
use App\Http\Controllers\Api\KaraokeController;
use App\Http\Controllers\Api\KaraokeSongController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\PublicPlayController;
use App\Http\Controllers\Api\PublicTotemController;
use App\Http\Controllers\Api\RecapController;
use App\Http\Controllers\Api\TargetController;
use App\Http\Controllers\Api\TimingController;
use App\Http\Controllers\Api\TotemController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserGameController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact-requests', [ContactRequestController::class, 'store'])->middleware('throttle:5,10');
Route::get('/preview/{token}', [PreviewController::class, 'show']);
Route::post('/preview/{token}/approve', [PreviewController::class, 'approve']);

Route::get('/play/{token}', [PublicPlayController::class, 'show']);
Route::get('/play/{token}/history', [PublicPlayController::class, 'history']);
Route::get('/play/{token}/ranking', [PublicPlayController::class, 'ranking']);
Route::post('/play/{token}/plays', [GamePlayController::class, 'store']);
Route::get('/play/{token}/karaoke-ranking', [KaraokeController::class, 'ranking']);
Route::get('/play/{token}/timing-ranking', [TimingController::class, 'ranking']);
Route::get('/play/{token}/target-ranking', [TargetController::class, 'ranking']);
Route::post('/play/{token}/plays/{gamePlay}/recording', [KaraokeController::class, 'uploadRecording']);
Route::get('/recap/{token}', [RecapController::class, 'showPublic']);

Route::get('/totem/{token}', [PublicTotemController::class, 'show']);
Route::post('/totem/{token}/unlock', [PublicTotemController::class, 'unlock']);
Route::post('/totem/{token}/events/{eventId}/unlock', [PublicTotemController::class, 'unlockEvent']);
Route::get('/totem/{token}/events/{eventId}/names', [PublicTotemController::class, 'names']);
Route::get('/totem/{token}/events/{eventId}/ranking', [PublicTotemController::class, 'ranking']);
Route::get('/totem/{token}/events/{eventId}/attract', [PublicTotemController::class, 'attract']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::middleware('admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::get('/contact-requests', [ContactRequestController::class, 'index']);
        Route::post('/games', [GameController::class, 'store']);
        Route::put('/games/{game}', [GameController::class, 'update']);
        Route::delete('/games/{game}', [GameController::class, 'destroy']);
    });

    Route::apiResource('events', EventController::class);
    Route::apiResource('totems', TotemController::class);
    Route::post('/totems/{totem}/public-link', [TotemController::class, 'generatePublicLink']);

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
    Route::post('/game-instances/{gameInstance}/public-link', [GameInstanceController::class, 'generatePublicLink']);

    Route::get('/events/{event}/recap', [RecapController::class, 'show']);
    Route::post('/events/{event}/recap/generate', [RecapController::class, 'generate']);

    Route::get('/events/{event}/leads', [LeadController::class, 'index']);
    Route::get('/events/{event}/leads/export', [LeadController::class, 'export']);

    Route::post('/uploads', [UploadController::class, 'store']);

    Route::get('/karaoke-songs', [KaraokeSongController::class, 'index']);
    Route::post('/karaoke-songs', [KaraokeSongController::class, 'store']);
    Route::put('/karaoke-songs/{karaokeSong}', [KaraokeSongController::class, 'update']);
    Route::delete('/karaoke-songs/{karaokeSong}', [KaraokeSongController::class, 'destroy']);
});
