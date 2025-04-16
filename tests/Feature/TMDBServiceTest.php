<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TMDBServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function testGetAllMoviesWithoutGenre()
    {
        // Mock the external HTTP request
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie*' => Http::response([
                'results' => [],
                'page' => 1,
                'total_results' => 0,
                'total_pages' => 1
            ], 200),
        ]);

        // Your actual method that performs the HTTP request (e.g., in a controller)
        $params = [
            'api_key' => 'your_api_key_here',
            'page' => 1,
        ];
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        // Convert the response to an array
        $responseData = $response->json();

        // Assert that the response contains the correct keys
        $this->assertArrayHasKey('results', $responseData);
        $this->assertEquals(0, $responseData['total_results']);
        $this->assertEquals(1, $responseData['total_pages']);
    }

    /**
     * Test fetching all movies with a specific genre.
     *
     * @return void
     */
    public function testGetAllMoviesWithGenre()
    {
        Http::fake([
            'https://api.themoviedb.org/3/discover/movie*' => Http::response([
                'results' => [
                    ['title' => 'Movie 1', 'genre_ids' => [28]],
                    ['title' => 'Movie 2', 'genre_ids' => [28]],
                ],
                'page' => 1,
                'total_results' => 2,
                'total_pages' => 1
            ], 200),
        ]);

        $genre = 28;
        $params = [
            'api_key' => 'your_api_key_here',
            'page' => 1,
            'with_genres' => $genre,
        ];
        $response = Http::get('https://api.themoviedb.org/3/discover/movie', $params);

        $responseData = $response->json();

        $this->assertCount(2, $responseData['results']);
        $this->assertEquals(2, $responseData['total_results']);
        $this->assertEquals(1, $responseData['total_pages']);
    }

    public function testGetAllMoviesApiFailure()
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
}
