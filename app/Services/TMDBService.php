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
