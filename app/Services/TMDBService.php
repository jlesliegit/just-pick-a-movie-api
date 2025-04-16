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
            'genres' => ! empty($movie['genres'])
                ? collect($movie['genres'])->pluck('name')->all()
                : ['Unknown Genre'],
            'description' => $movie['overview'] ?? 'No description available.',
            'runtime' => $movie['runtime'] ?? 0,
            'rating' => $movie['vote_average'] ?? 'N/A',
            'year' => isset($movie['release_date']) && $movie['release_date'] !== ''
                ? substr($movie['release_date'], 0, 4)
                : 'Unknown',
            'image' => ! empty($movie['backdrop_path'])
                ? 'https://image.tmdb.org/t/p/w1280'.$movie['backdrop_path']
                : 'https://via.placeholder.com/1280x720?text=No+Image+Available',
        ];
        return response()->json([
            'message' => 'Movie fetched successfully',
            'data' => $movie
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

    use Illuminate\Support\Facades\Http;
    use Tests\TestCase;

class MovieTest extends TestCase
{
    /**
     * Test fetching a single movie with valid data.
     *
     * @return void
     */
    public function testGetSingleMovie()
    {
        // Mock the external HTTP request for a single movie
        Http::fake([
            'https://api.themoviedb.org/3/movie/*' => Http::response([
                'title' => 'Inception',
                'genres' => [
                    ['name' => 'Action'],
                    ['name' => 'Sci-Fi']
                ],
                'overview' => 'A mind-bending thriller',
                'runtime' => 148,
                'vote_average' => 8.8,
                'release_date' => '2010-07-16',
                'backdrop_path' => '/path_to_image.jpg',
            ], 200),
        ]);

        // Test with a movie ID (e.g., ID = 123)
        $movieId = 123;
        $response = Http::get("https://api.themoviedb.org/3/movie/{$movieId}", [
            'api_key' => 'your_api_key_here',
        ]);

        // Convert the response to an array
        $responseData = $response->json();

        // Assert the response structure and data
        $this->assertEquals('Inception', $responseData['data']['title']);
        $this->assertEquals(['Action', 'Sci-Fi'], $responseData['data']['genres']);
        $this->assertEquals('A mind-bending thriller', $responseData['data']['description']);
        $this->assertEquals(148, $responseData['data']['runtime']);
        $this->assertEquals(8.8, $responseData['data']['rating']);
        $this->assertEquals('2010', $responseData['data']['year']);
        $this->assertEquals('https://image.tmdb.org/t/p/w1280/path_to_image.jpg', $responseData['data']['image']);
    }

    public function testGetSingleMovieWithMissingData()
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

        $movieId = 123;
        $response = Http::get("https://api.themoviedb.org/3/movie/{$movieId}", [
            'api_key' => 'your_api_key_here',
        ]);

        $responseData = $response->json();

        $this->assertEquals('No data found', $responseData['data']);
    }


    public function testGetSingleMovieApiFailure()
    {
        Http::fake([
            'https://api.themoviedb.org/3/movie/*' => Http::response([], 500),
        ]);

        $movieId = 123;
        $response = Http::get("https://api.themoviedb.org/3/movie/{$movieId}", [
            'api_key' => 'your_api_key_here',
        ]);

        $responseData = $response->json();

        $this->assertEmpty($responseData);
    }

}
