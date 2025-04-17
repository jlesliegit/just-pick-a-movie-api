<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mood extends Model
{
    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    public function genres():BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

}
