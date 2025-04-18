<?php

namespace App\Http\Controllers;

use App\Models\Genre;
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

        return response()->json([
            'message' => 'Popular movies successfully fetched',
            'data' => $movies
        ]);
    }

    public function getMoviesByMood($moodName): JsonResponse
    {
        $mood = Mood::where('name', $moodName)->first();
        $page = request()->query('page', 1);

        if (! $mood) {
            return response()->json(['error' => 'Mood not found'], 404);
        }

        $genreIds = $mood->genres->pluck('id')->toArray();
        $allMovies = collect();
        $genreName = Genre::pluck('name', 'id')->toArray();
        $totalPages = 1;

        foreach ($genreIds as $genreId) {
            $response = $this->tmdbService->getMoviesByGenre($genreId, $page);
            $allMovies = $allMovies->merge($response['results'] ?? []);
            $totalPages = max($totalPages, $response['total_pages'] ?? 1);
        }

        $filteredMovies = $allMovies
            ->filter(function ($movie) use ($genreIds) {
                $matchingGenres = array_intersect($movie['genre_ids'], $genreIds);

                return count($matchingGenres) >= 2;
            })
            ->unique('id')
            ->map(function ($movie) use ($genreName) {
                $genreNames = collect($movie['genre_ids'] ?? [])
                    ->map(fn ($id) => $genreName[$id] ?? null)
                    ->filter()
                    ->values();

                return [
                    'title' => $movie['title'] ?? null,
                    'genres' => $genreNames,
                    'description' => $movie['overview'] ?? null,
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
