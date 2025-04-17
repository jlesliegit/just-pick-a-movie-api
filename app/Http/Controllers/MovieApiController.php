<?php

namespace App\Http\Controllers;

use App\Models\Mood;
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

    public function getMoviesByMood($moodName)
    {
        $mood = Mood::where('name', $moodName)->first();
        $page = \request()->query('page', 1);

        if ($mood) {
            $genres = $mood->genres;
            $genreIds = $genres->pluck('id')->toArray();

            $movieData = $this->tmdbService->getMoviesByGenre($genreIds, $page);

            if ($movieData && isset($movieData['results'])) {
                $filteredMovies = collect($movieData['results'])->map(function ($movie) {
                    return [
                        'title' => $movie['title'] ?? null,
                        'genres' => $movie['genre_ids'] ?? [],
                        'description' => $movie['overview'] ?? null,
                        'runtime' => $movie['runtime'] ?? null,
                        'rating' => $movie['vote_average'] ?? null,
                        'year' => $movie['release_date'] ? substr($movie['release_date'], 0, 4) : null,
                        'image' => $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path'] : null,
                    ];
                });

                return response()->json([
                    'message' => "$moodName movies fetched successfully",
                    'data' => $filteredMovies,
                ]);
            }

            return response()->json(['error' => 'No movies found'], 404);
        } else {
            return response()->json(['error' => 'Mood not found'], 404);
        }
    }
}
