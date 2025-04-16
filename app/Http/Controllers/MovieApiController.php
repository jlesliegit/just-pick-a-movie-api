<?php

namespace App\Http\Controllers;

use App\Services\TMDBService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovieApiController extends Controller
{
    protected $tmdbService;

    public function __construct(TMDBService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    public function getMovies(Request $request): JsonResponse
    {
        $genre = $request->input('genre');
        $page = $request->input('page', 1);

        $movies = $this->tmdbService->getAllMovies($page, $genre);

        return response()->json($movies);
    }

    public function getPopularMovies(): JsonResponse
    {
        $movies = $this->tmdbService->getPopularMovies();

        return response()->json($movies);
    }
}
