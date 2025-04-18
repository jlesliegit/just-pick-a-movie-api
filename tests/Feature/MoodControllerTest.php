<?php

namespace Tests\Feature;

use App\Models\Mood;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class MoodControllerTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic feature test example.
     */
    public function test_get_all_moods_success(): void
    {
        Mood::factory()->count(3)->create();

        $response = $this->getJson('/api/mood');
        $response->assertStatus(200)
            ->assertJson(function(AssertableJson $json) {
                $json->hasAll(['message', 'data'])
                    ->has('data', 3, function(AssertableJson $data) {
                        $data->hasAll(['id', 'name']);
                    });
            });
    }

}
