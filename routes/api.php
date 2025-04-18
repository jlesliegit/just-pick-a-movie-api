<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\MovieApiController;
use App\Services\TMDBService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/movies', [TMDBService::class, 'getAllMovies']);
Route::get('/popular-movies', [MovieApiController::class, 'getPopularMovies']);
Route::get('/movies/{movie}', [TMDBService::class, 'getSingle']);
Route::get('movies/genres/{genre}', [TMDBService::class, 'getMoviesByGenre']);
Route::get('/movies/mood/{mood}', [MovieApiController::class, 'getMoviesByMood']);
Route::get('/mood', [MoodController::class, 'all']);
Route::get('/genre', [GenreController::class, 'all']);
