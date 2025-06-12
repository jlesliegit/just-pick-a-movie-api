<?php

namespace App\Services;

use App\Models\Genre;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TMDBService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.themoviedb.org/3';

    public function __construct()
    {
        $this->apiKey = env('TMDB_API_KEY');
    }

    public function getMoviesByGenre($genreId, $page = 1): array
    {
        $url = 'https://api.themoviedb.org/3/discover/movie';
        $params = [
            'api_key' => $this->apiKey,
            'with_genres' => $genreId,
            'page' => $page,
            'language' => 'en-US',
            'include_adult' => false,
        ];

        $response = Http::retry(3, 100)->get($url, $params);

        if ($response->failed()) {
            throw new \Exception('TMDB API failed: '.$response->body());
        }

        $data = $response->json();

        // Get genre names mapping (you might want to cache this)
        $genreList = $this->getGenreList();

        $formattedResults = [];
        foreach ($data['results'] as $movie) {
            $formattedResults[] = $this->formatMovieData($movie, $genreList);
        }

        return [
            'success' => ! empty($formattedResults),
            'page' => $data['page'] ?? $page,
            'total_pages' => $data['total_pages'] ?? 0,
            'total_results' => $data['total_results'] ?? 0,
            'results' => $formattedResults,
            'genre_id' => $genreId,
        ];
    }

    protected function getGenreList(): array
    {
        $response = Http::get('https://api.themoviedb.org/3/genre/movie/list', [
            'api_key' => $this->apiKey,
            'language' => 'en-US',
        ]);

        if ($response->successful()) {
            $genres = $response->json()['genres'] ?? [];

            return array_combine(array_column($genres, 'id'), array_column($genres, 'name'));
        }

        return [];
    }

    protected function formatMovieData(array $movie, array $genreList): array
    {
        $releaseDate = null;
        $year = null;

        if (! empty($movie['release_date'])) {
            try {
                $date = new DateTime($movie['release_date']);
                $releaseDate = $date->format('d/m/Y');
                $year = $date->format('Y');
            } catch (Exception $e) {
                $releaseDate = $movie['release_date'];
                $year = substr($movie['release_date'], 0, 4);
            }
        }

        return [
            'id' => $movie['id'] ?? null,
            'title' => $movie['title'] ?? null,
            'genres' => array_filter(array_map(function ($genreId) use ($genreList) {
                return $genreList[$genreId] ?? null;
            }, $movie['genre_ids'] ?? [])),
            'description' => $movie['overview'] ?? null,
            'rating' => isset($movie['vote_average']) ? round($movie['vote_average'], 1) : null,
            'release_date' => $releaseDate,
            'year' => $year,
            'poster' => $movie['poster_path'] ? "https://image.tmdb.org/t/p/w500{$movie['poster_path']}" : null,
            'backdrop' => $movie['backdrop_path'] ? "https://image.tmdb.org/t/p/w1280{$movie['backdrop_path']}" : null,
        ];
    }

    public function getMovieDetails(int $movieId): array
    {
        $response = Http::get("{$this->baseUrl}/movie/{$movieId}", [
            'api_key' => $this->apiKey,
            'language' => 'en-US',
            'append_to_response' => 'credits,videos',
        ]);

        return $response->json();
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
                try {
                    $details = $this->getMovieDetails($movie['id']);

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
                    \Log::error("Failed to fetch details for movie {$movie['id']}: ".$e->getMessage());

                    return null;
                }
            })
            ->filter()
            ->values();

        if ($formattedMovies->isEmpty()) {
            return response()->json(['error' => 'No movies found'], 404);
        }

        return response()->json([
            'page' => $movies['page'] ?? 1,
            'total_pages' => $movies['total_pages'] ?? 1,
            'total_results' => $movies['total_results'] ?? 0,
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
            'id' => $movie['id'] ?? null,
            'title' => $movie['title'] ?? 'Unknown Title',
            'genres' => ! empty($movie['genres'])
                ? collect($movie['genres'])->pluck('name')->all()
                : ['Unknown Genre'],
            'description' => $movie['overview'] ?? 'No description available.',
            'tagline' => $movie['tagline'] ?? 'No tagline available.',
            'rating' => $movie['vote_average'] ?? 'N/A',
            'runtime' => $movie['runtime'] ?? 0,
            'release_date' => isset($movie['release_date']) && $movie['release_date'] !== ''
                ? date('d/m/Y', strtotime($movie['release_date']))
                : 'Unknown',
            'year' => isset($movie['release_date']) && $movie['release_date'] !== ''
                ? substr($movie['release_date'], 0, 4)
                : 'Unknown',
            'poster' => ! empty($movie['poster_path'])
                ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path']
                : 'https://via.placeholder.com/500x750?text=No+Poster',
            'backdrop' => ! empty($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280'.$movie['backdrop_path']
                : 'https://via.placeholder.com/1280x720?text=No+Image+Available',
            'similar_movies' => $formattedSimilar,
        ];

        return response()->json([
            'message' => 'Movie fetched successfully',
            'data' => $movie,
        ]);
    }
}
