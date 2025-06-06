<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mood_genre', function (Blueprint $table) {
            $table->foreignId('mood_id');
            $table->bigInteger('tmdb_genre_id');
            $table->timestamps();

            $table->primary(['mood_id', 'tmdb_genre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mood_genre');
    }
};
