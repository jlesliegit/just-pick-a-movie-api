<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moods = [
            'Happy',
            'Sad',
            'Scared',
            'Romantic',
            'Curious',
            'Gripping',
            'Serious',
            'Gritty',
            'Chill'
        ];

        foreach ($moods as $mood) {
            DB::table('moods')->insert([
                'name' => $mood,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
