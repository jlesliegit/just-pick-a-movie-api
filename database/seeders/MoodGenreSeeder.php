<?php

namespace Database\Seeders;

use App\Models\Mood;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoodGenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moodGenreMap = [
            'Happy' => [35, 16, 10751, 10402],
            'Sad' => [18, 10749, 10402],
            'Scared' => [27, 53, 9648],
            'Romantic' => [10749, 18, 10402],
            'Curious' => [99, 36, 878, 9648],
            'Gripping' => [9648, 80, 53],
            'Serious' => [10752, 36, 18],
            'Gritty' => [37, 80, 53],
            'Chill' => [10770, 35, 16]
        ];

        foreach ($moodGenreMap as $moodName => $genreIds) {
            $mood = Mood::where('name', $moodName)->first();

            if ($mood) {
                foreach ($genreIds as $genreId) {
                    DB::table('mood_genre')->updateOrInsert([
                        'mood_id' => $mood->id,
                        'tmdb_genre_id' => $genreId,
                    ]);
                }
            }
        }
    }
}
