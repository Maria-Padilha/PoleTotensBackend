<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserGameResource;
use Illuminate\Http\Request;

class UserGameController extends Controller
{
    public function index(Request $request)
    {
        $userGames = $request->user()->userGames()->with('game')->get();

        return UserGameResource::collection($userGames);
    }
}
