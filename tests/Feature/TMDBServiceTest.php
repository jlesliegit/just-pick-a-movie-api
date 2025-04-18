<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TMDBServiceTest extends TestCase
{
    public function test_get_all_movies_without_genre()
    {
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie*' => Http::response([
                'results' => [],
                'page' => 1,
                'total_results' => 0,
                'total_pages' => 1,
            ], 200),
        ]);

        $params = [
            'api_key' => '',
            'page' => 1,
        ];
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        $responseData = $response->json();

        $this->assertArrayHasKey('results', $responseData);
        $this->assertEquals(0, $responseData['total_results']);
        $this->assertEquals(1, $responseData['total_pages']);
    }

    public function test_get_all_movies_with_genre()
    {
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie*' => Http::response([
                'results' => [
                    ['title' => 'Movie 1', 'genre_ids' => [28]],
                    ['title' => 'Movie 2', 'genre_ids' => [28]],
                ],
                'page' => 1,
                'total_results' => 2,
                'total_pages' => 1,
            ], 200),
        ]);

        $genre = 28;
        $params = [
            'api_key' => '',
            'page' => 1,
            'with_genres' => $genre,
        ];
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        $responseData = $response->json();

        $this->assertCount(2, $responseData['results']);
        $this->assertEquals(2, $responseData['total_results']);
        $this->assertEquals(1, $responseData['total_pages']);
    }

    public function test_get_all_movies_api_failure()
    {
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie*' => Http::response([], 500),
        ]);

        $params = [
            'api_key' => '',
            'page' => 1,
        ];
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        $responseData = $response->json();

        $this->assertEmpty($responseData);
    }

    public function test_returns_movie_data_successfully()
    {
        Http::fake([
            'https://api.themoviedb.org/3/movie/*' => Http::response([
                'title' => 'Inception',
                'genres' => [
                    ['name' => 'Action'],
                    ['name' => 'Sci-Fi'],
                ],
                'overview' => 'A mind-bending thriller',
                'runtime' => 148,
                'vote_average' => 8.8,
                'release_date' => '2010-07-16',
                'backdrop_path' => '/inception.jpg',
            ], 200),
        ]);

        $response = $this->getJson('/api/movies/123');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Movie fetched successfully',
            'data' => [
                'title' => 'Inception',
                'genres' => ['Action', 'Sci-Fi'],
                'description' => 'A mind-bending thriller',
                'runtime' => 148,
                'rating' => 8.8,
                'year' => '2010',
                'image' => 'https://image.tmdb.org/t/p/w1280/inception.jpg',
            ],
        ]);
    }

    public function test_handles_missing_movie_data()
    {
        Http::fake([
            'https://api.themoviedb.org/3/movie/*' => Http::response([
                'title' => '',
                'genres' => [],
                'overview' => '',
                'runtime' => null,
                'vote_average' => null,
                'release_date' => '',
                'backdrop_path' => '',
            ], 200),
        ]);

        $response = $this->getJson('/api/movies/900');

        $response->assertStatus(404);
        $response->assertJson([
            'error' => 'No data found',
        ]);
    }

    public function test_handles_api_failure()
    {
        Http::fake([
            'https://api.themoviedb.org/3/movie/*' => Http::response([], 500),
        ]);

        $response = $this->getJson('/api/movies/789');

        $response->assertStatus(500);
        $response->assertJson([
            'error' => 'Failed to fetch data',
        ]);
    }
}
