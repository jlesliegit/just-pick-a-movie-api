<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mood extends Model
{
    protected $fillable = ['name'];

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'mood_genre', 'mood_id', 'tmdb_genre_id');
    }
}
