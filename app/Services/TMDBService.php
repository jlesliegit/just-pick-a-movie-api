<?php

namespace App\Services;

use App\Models\Mood;
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
            return ['No data found'];
        }

        $movie = [
            'title' => $movie['title'] ?? 'Unknown Title',
            'genres' => !empty($movie['genres'])
                ? collect($movie['genres'])->pluck('name')->all()
                : ['Unknown Genre'],
            'description' => $movie['overview'] ?? 'No description available.',
            'runtime' => $movie['runtime'] ?? 0,
            'rating' => $movie['vote_average'] ?? 'N/A',
            'year' => isset($movie['release_date']) && $movie['release_date'] !== ''
                ? substr($movie['release_date'], 0, 4)
                : 'Unknown',
            'image' => !empty($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280' . $movie['backdrop_path']
                : 'https://via.placeholder.com/1280x720?text=No+Image+Available',
        ];

        return response()->json([
            'message' => 'Movie fetched successfully',
            'data' => $movie,
        ]);
    }

    public function getMoviesByGenre($genreId, $page = 1)
    {
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', [
            'api_key' => $this->apiKey,
            'page' => $page,
            'with_genres' => $genreId,
        ]);

        return $response->json();
    }

}
