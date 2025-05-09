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
        $response = $this->tmdbService->getPopularMovies();
        $movies = $response['results'] ?? [];

        $genreName = Genre::pluck('name', 'id')->toArray();

        $formattedMovies = collect($movies)
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

        if ($formattedMovies->isEmpty()) {
            return response()->json(['error' => 'No popular movies found'], 404);
        }

        return response()->json([
            'message' => 'Popular movies successfully fetched',
            'data' => $formattedMovies,
        ]);
    }

    public function getMoviesByMood($moodName): JsonResponse
    {
        $mood = Mood::with('genres')->where('name', $moodName)->first();

        if (!$mood) {
            return response()->json([
                'error' => 'Mood not found',
                'available_moods' => Mood::pluck('name')->toArray()
            ], 404);
        }

        $tmdbGenreIds = $mood->genres->pluck('id')
            ->filter(fn($id) => is_numeric($id) && $id > 0)
            ->unique()
            ->values()
            ->toArray();

        if (empty($tmdbGenreIds)) {
            return response()->json([
                'error' => 'No valid genres associated with this mood',
                'mood_details' => [
                    'name' => $mood->name,
                    'genre_count' => $mood->genres->count(),
                    'genre_sample' => $mood->genres->take(3)->pluck('name', 'id')
                ]
            ], 400);
        }

        $allMovies = collect();
        $errors = [];
        $page = request()->query('page', 20);

        foreach ($tmdbGenreIds as $genreId) {
            try {
                $data = $this->tmdbService->getMoviesByGenre($genreId, $page);
                $movies = $data['results'] ?? [];

                \Log::debug("TMDB API Response for Genre ID {$genreId}: ", ['movie_count' => count($movies)]);

                $allMovies = $allMovies->merge($movies);
            } catch (\Exception $e) {
                $errors[$genreId] = $e->getMessage();
            }
        }

        $filteredMovies = $allMovies->filter(function ($movie) use ($tmdbGenreIds) {
            $genreIds = $movie['genre_ids'] ?? [];
            $matchCount = collect($genreIds)->intersect($tmdbGenreIds)->count();
            return $matchCount >= 2;
        });

        $uniqueMovies = $filteredMovies->unique('id')->values();

        if ($uniqueMovies->isEmpty()) {
            \Log::warning("No matching movies found for mood '$moodName'", [
                'genre_ids' => $tmdbGenreIds,
                'errors' => $errors
            ]);

            return response()->json([
                'error' => 'No movies found for these genres',
                'technical_details' => [
                    'tried_genres' => $tmdbGenreIds,
                    'api_errors' => $errors
                ]
            ], 404);
        }

        $genreNameMap = Genre::pluck('name', 'id')->toArray();
        $moviesWithNames = $uniqueMovies->map(function ($movie) use ($genreNameMap) {
            $genreNames = collect($movie['genre_ids'] ?? [])
                ->map(fn ($id) => $genreNameMap[$id] ?? null)
                ->filter()
                ->values()
                ->all();

            $movie['genres'] = $genreNames;
            unset($movie['genre_ids']);
            return $movie;
        });

        return response()->json([
            'message' => "$moodName movies fetched successfully",
            'data' => $moviesWithNames
        ]);
    }

}
