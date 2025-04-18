<?php

namespace Tests\Feature;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        Genre::factory()->count(3)->create();

        $response = $this->getJson('api/genre');
        $response->assertStatus(200)
            ->assertJson(function (AssertableJson $json) {
                $json->hasAll(['message', 'data'])
                    ->has('data', 3, function (AssertableJson $data) {
                        $data->hasAll(['id', 'name']);
                    });
            });
    }
}
