<?php

namespace App\Http\Controllers;

use App\Models\Mood;
use App\Models\Genre;
use App\Services\TmdbService;
use Illuminate\Http\JsonResponse;

class MovieApiController extends Controller
{
    protected TmdbService $tmdbService;

    public function __construct(TmdbService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
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
        $page = request()->query('page', 1);

        foreach ($tmdbGenreIds as $genreId) {
            try {
                $data = $this->tmdbService->getMoviesByGenre($genreId, $page);
                $movies = $data['results'] ?? [];
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
            return response()->json([
                'error' => 'No movies found for these genres',
                'technical_details' => [
                    'tried_genres' => $tmdbGenreIds,
                    'api_errors' => $errors
                ]
            ], 404);
        }

        $genreNameMap = Genre::pluck('name', 'id')->toArray();
        $moviesWithDetails = collect();

        foreach ($uniqueMovies as $movie) {
            try {
                $details = $this->tmdbService->getMovieDetails($movie['id']);

                $genreNames = collect($movie['genre_ids'] ?? [])
                    ->map(fn($id) => $genreNameMap[$id] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $moviesWithDetails->push([
                    'id' => $movie['id'],
                    'title' => $movie['title'] ?? $details['title'] ?? 'Unknown Title',
                    'genres' => !empty($genreNames) ? $genreNames : ['Unknown Genre'],
                    'description' => $movie['overview'] ?? $details['overview'] ?? 'No description available.',
                    'tagline' => $details['tagline'] ?? 'No tagline available.',
                    'rating' => $movie['vote_average'] ?? $details['vote_average'] ?? 'N/A',
                    'runtime' => $details['runtime'] ?? 'Not Available',
                    'release_date' => $movie['release_date'] ?? $details['release_date'] ?? null,
                    'year' => isset($movie['release_date']) && $movie['release_date'] !== ''
                        ? substr($movie['release_date'], 0, 4)
                        : (isset($details['release_date']) ? substr($details['release_date'], 0, 4) : 'Unknown'),
                    'poster' => !empty($movie['poster_path'])
                        ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path']
                        : 'https://via.placeholder.com/500x750?text=No+Poster',
                    'backdrop' => !empty($movie['backdrop_path'])
                        ? 'https://image.tmdb.org/t/p/w1280' . $movie['backdrop_path']
                        : 'https://via.placeholder.com/1280x720?text=No+Backdrop',
                    'imdb_id' => $details['imdb_id'] ?? null,
                    'status' => $details['status'] ?? null,
                ]);
            } catch (\Exception $e) {
                \Log::error("Failed to process movie {$movie['id']}: " . $e->getMessage());
                continue;
            }
        }

        return response()->json([
            'message' => "$moodName movies fetched successfully",
            'count' => $moviesWithDetails->count(),
            'data' => $moviesWithDetails
        ]);
    }

    public function getPopularMovies(): JsonResponse
    {
        $response = $this->tmdbService->getPopularMovies();
        $movies = $response['results'] ?? [];

        $genreName = Genre::pluck('name', 'id')->toArray();

        $formattedMovies = collect($movies)
            ->map(function ($movie) use ($genreName) {
                try {
                    $details = $this->tmdbService->getMovieDetails($movie['id']);

                    $genreNames = collect($movie['genre_ids'] ?? [])
                        ->map(fn ($id) => $genreName[$id] ?? null)
                        ->filter()
                        ->values();

                    return [
                        'id' => $movie['id'],
                        'title' => $movie['title'] ?? $details['title'] ?? null,
                        'genres' => $genreNames,
                        'description' => $movie['overview'] ?? $details['overview'] ?? null,
                        'rating' => $movie['vote_average'] ?? $details['vote_average'] ?? null,
                        'runtime' => $details['runtime'] ?? null,
                        'release_date' => isset($movie['release_date'])
                            ? date('d/m/Y', strtotime($movie['release_date']))
                            : (isset($details['release_date'])
                                ? date('d/m/Y', strtotime($details['release_date']))
                                : null),
                        'year' => $movie['release_date']
                            ? substr($movie['release_date'], 0, 4)
                            : (isset($details['release_date'])
                                ? substr($details['release_date'], 0, 4)
                                : null),
                        'poster' => $movie['poster_path']
                            ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path']
                            : null,
                        'backdrop' => $movie['backdrop_path']
                            ? 'https://image.tmdb.org/t/p/w1280'.$movie['backdrop_path']
                            : null,
                        'tagline' => $details['tagline'] ?? null,
                    ];
                } catch (\Exception $e) {
                    \Log::error("Failed to fetch details for movie {$movie['id']}: " . $e->getMessage());
                    return null;
                }
            })
            ->filter()
            ->values();

        if ($formattedMovies->isEmpty()) {
            return response()->json(['error' => 'No popular movies found'], 404);
        }

        return response()->json([
            'message' => 'Popular movies successfully fetched',
            'data' => $formattedMovies,
        ]);
    }

}
