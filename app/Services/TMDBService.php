<?php

namespace App\Services;

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

    public function getSingle(int $id): array
    {
        $response = Http::get("https://api.themoviedb.org/3/movie/$id", [
            'api_key' => $this->apiKey,
        ]);

        $movie = $response->json();

        return [
            'title' => $movie['title'] ?? null,
            'genres' => collect($movie['genres'])->pluck('name')->all(),
            'description' => $movie['overview'] ?? null,
            'runtime' => $movie['runtime'] ?? null,
            'rating' => $movie['vote_average'] ?? null,
            'year' => isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
            'image' => isset($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280' . $movie['backdrop_path']
                : null,
        ];
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
