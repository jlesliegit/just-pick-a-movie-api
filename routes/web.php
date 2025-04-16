<?php

use App\Http\Controllers\MovieApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/movies', [MovieApiController::class, 'getMovies']);
Route::get('/popular-movies', [MovieApiController::class, 'showPopularMovies']);
