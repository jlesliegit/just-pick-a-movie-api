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

        if (! $mood) {
            return response()->json(['error' => 'Mood not found'], 404);
        }

        $genreIds = $mood->genres->pluck('id')->toArray();
        $allMovies = collect();

        foreach ($genreIds as $genreId) {
            $response = $this->tmdbService->getMoviesByGenre($genreId, $page);
            $allMovies = $allMovies->merge($response['results'] ?? []);
        }

        $filteredMovies = $allMovies
            ->filter(function ($movie) use ($genreIds) {
                $matchingGenres = array_intersect($movie['genre_ids'], $genreIds);

                return count($matchingGenres) >= 2;
            })
            ->unique('id')
            ->map(function ($movie) {
                return [
                    'title' => $movie['title'] ?? null,
                    'genres' => $movie['genre_ids'] ?? [],
                    'description' => $movie['overview'] ?? null,
                    'runtime' => $movie['runtime'] ?? null,
                    'rating' => $movie['vote_average'] ?? null,
                    'year' => $movie['release_date'] ? substr($movie['release_date'], 0, 4) : null,
                    'image' => $movie['poster_path'] ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path'] : null,
                ];
            })
            ->values();

        if ($filteredMovies->isEmpty()) {
            return response()->json(['error' => 'No matching movies found for this mood'], 404);
        }

        return response()->json([
            'message' => "$moodName movies fetched successfully",
            'data' => $filteredMovies,
        ]);
    }
}
