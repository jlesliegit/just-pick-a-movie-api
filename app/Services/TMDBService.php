<?php

namespace App\Services;

use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TMDBService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('TMDB_API_KEY');
    }

    protected function formatMoviesResponse(array $apiResponse, array $filterGenreIds = []): array
    {
        $genreNameMap = Genre::pluck('name', 'id')->toArray();

        $movies = collect($apiResponse['results'] ?? [])
            ->map(function ($movie) use ($genreNameMap, $filterGenreIds) {
                $genreIds = empty($filterGenreIds)
                    ? ($movie['genre_ids'] ?? [])
                    : array_intersect($movie['genre_ids'] ?? [], $filterGenreIds);

                $genreNames = collect($genreIds)
                    ->map(fn ($id) => $genreNameMap[$id] ?? null)
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
            ->values()
            ->toArray();

        return [
            'page' => $apiResponse['page'] ?? 1,
            'total_pages' => $apiResponse['total_pages'] ?? 1,
            'total_results' => $apiResponse['total_results'] ?? 0,
            'results' => $movies,
        ];
    }

    public function getAllMovies($page = 1, $genre = null): JsonResponse
    {
        $params = [
            'api_key' => $this->apiKey,
            'page' => $page,
        ];

        if ($genre) {
            $params['with_genres'] = $genre;
        }

        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);
        $movies = $response->json();

        $genreName = Genre::pluck('name', 'id')->toArray();

        $formattedMovies = collect($movies['results'] ?? [])
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
            return response()->json(['error' => 'No movies found'], 404);
        }

        return response()->json([
            'message' => 'Movies fetched successfully',
            'data' => $formattedMovies,
        ]);
    }

    public function getPopularMovies()
    {
        $response = Http::get('https://api.themoviedb.org/3/movie/popular', [
            'api_key' => $this->apiKey,
        ]);

        return $response->json();
    }

    public function getSingle(int $id): JsonResponse|array
    {
        $response = Http::get("https://api.themoviedb.org/3/movie/$id", [
            'api_key' => $this->apiKey,
        ]);

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
            return response()->json(['error' => 'No data found'], 404);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Failed to fetch data'], 500);
        }

        $similarResponse = Http::get("https://api.themoviedb.org/3/movie/{$id}/similar", [
            'api_key' => $this->apiKey,
        ]);

        $similarMovies = $similarResponse->json()['results'] ?? [];

        $formattedSimilar = collect($similarMovies)
            ->take(5)
            ->map(function ($similar) {
                return [
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

    public function getGenreIdByName($genreName)
    {
        $response = Http::get('https://api.themoviedb.org/3/genre/movie/list', [
            'api_key' => $this->apiKey,
        ]);

        $genres = collect($response->json('genres'));

        $genre = $genres->firstWhere('name', ucfirst(strtolower($genreName)));

        return $genre['id'] ?? null;
    }

    public function getMoviesByGenre($genreNameOrId, $page = 1): array
    {
        if (! is_numeric($genreNameOrId)) {
            $genreNames = is_array($genreNameOrId) ? $genreNameOrId : [$genreNameOrId];
            $genreIds = [];

            foreach ($genreNames as $name) {
                $id = $this->getGenreIdByName($name);
                if ($id) {
                    $genreIds[] = $id;
                }
            }

            if (empty($genreIds)) {
                return [];
            }
        } else {
            $genreIds = [$genreNameOrId];
        }

        $response = Http::get('https://api.themoviedb.org/3/discover/movie', [
            'api_key' => $this->apiKey,
            'page' => $page,
            'with_genres' => implode(',', $genreIds),
        ]);

        return $response->json();
    }
}
