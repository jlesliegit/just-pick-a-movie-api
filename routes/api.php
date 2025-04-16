<?php

use App\Http\Controllers\MovieApiController;
use App\Services\TMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/popular-movies', [MovieApiController::class, 'getPopularMovies']);
Route::get('/movies', [TMDBService::class, 'getAllMovies']);
Route::get('/movies/{movie}', [TMDBService::class, 'getSingle']);
