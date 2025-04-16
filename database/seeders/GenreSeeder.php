<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $response = Http::get('https://api.themoviedb.org/3/genre/movie/list', [
            'api_key' => env('TMDB_API_KEY'),
            'language' => 'en-US'
        ]);

        $genres = $response->json()['genres'];

        foreach ($genres as $genre) {
            DB::table('genres')->insert([
                'id' => $genre['id'],
                'name' => $genre['name']
            ]);
        }
    }
}
