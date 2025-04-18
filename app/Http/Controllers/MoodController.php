<?php

namespace App\Http\Controllers;

use App\Models\Mood;
use Illuminate\Database\Eloquent\Collection;

class MoodController extends Controller
{
    public function all(): Collection
    {
        return Mood::all(['id', 'name']);
    }
}
