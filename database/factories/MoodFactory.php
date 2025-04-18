<?php

namespace Database\Factories;

use App\Models\Mood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mood>
 */
class MoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
        ];
    }
}
