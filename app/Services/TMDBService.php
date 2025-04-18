<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TMDBService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('TMDB_API_KEY');
    }

    public function getAllMovies($page = 1, $genre = null)
    {
        $params = [
            'api_key' => $this->apiKey,
            'page' => $page,
        ];

        if ($genre) {
            $params['with_genres'] = $genre;
        }

        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        return $response->json();
    }

    public function getPopularMovies()
    {
        $response = Http::get('https://api.themoviedb.org/3/movie/popular', [
            'api_key' => $this->apiKey,
        ]);

        return $response->json();
    }

    public function getSingle(int $id): JsonResponse
    {
        $response = Http::get("https://api.themoviedb.org/3/movie/$id", [
            'api_key' => $this->apiKey,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'TMDB API request failed'], $response->status());
        }

        $movie = $response->json();

        if (
            empty($movie['title']) &&
            empty($movie['genres']) &&
            empty($movie['overview']) &&
            empty($movie['runtime']) &&
            empty($movie['vote_average']) &&
            empty($movie['release_date']) &&
            empty($movie['backdrop_path'])
        ) {
            return response()->json(['error' => 'No movie data found'], 404);
        }

        $similarResponse = Http::get("https://api.themoviedb.org/3/movie/{$id}/similar", [
            'api_key' => $this->apiKey,
        ]);

        $similarMovies = $similarResponse->json()['results'] ?? [];

        $formattedSimilar = collect($similarMovies)
            ->take(5)
            ->map(function ($similar) {
                return [
                    'id' => $similar['id'] ?? null,
                    'title' => $similar['title'] ?? null,
                    'image' => ! empty($similar['poster_path'])
                        ? 'https://image.tmdb.org/t/p/w500'.$similar['poster_path']
                        : 'https://via.placeholder.com/500x750?text=No+Image',
                ];
            });

        $movie = [
            'title' => $movie['title'] ?? 'Unknown Title',
            'genres' => ! empty($movie['genres'])
                ? collect($movie['genres'])->pluck('name')->all()
                : ['Unknown Genre'],
            'description' => $movie['overview'] ?? 'No description available.',
            'tagline' => $movie['tagline'] ?? 'No tagline available.',
            'runtime' => $movie['runtime'] ?? 0,
            'rating' => $movie['vote_average'] ?? 'N/A',
            'year' => isset($movie['release_date']) && $movie['release_date'] !== ''
                ? substr($movie['release_date'], 0, 4)
                : 'Unknown',
            'image' => ! empty($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280'.$movie['backdrop_path']
                : 'https://via.placeholder.com/1280x720?text=No+Image+Available',
            'similar_movies' => $formattedSimilar,
        ];

        return response()->json([
            'message' => 'Movie fetched successfully',
            'data' => $movie,
        ]);
    }

    public function getMoviesByGenre($genreId, $page = 1)
    {
        $genreParam = is_array($genreId) ? implode(',', $genreId) : $genreId;

        $response = Http::get('https://api.themoviedb.org/3/discover/movie', [
            'api_key' => $this->apiKey,
            'page' => $page,
            'with_genres' => $genreParam,
        ]);

        return $response->json();
    }
}
